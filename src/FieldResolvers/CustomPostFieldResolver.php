<?php

declare(strict_types=1);

namespace PoP\Stances\FieldResolvers;

use PoP\CustomPosts\Facades\CustomPostTypeAPIFacade;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\Schema\TypeCastingHelpers;
use PoP\Stances\TypeResolvers\StanceTypeResolver;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\LooseContracts\Facades\NameResolverFacade;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\AbstractDBDataFieldResolver;
use PoP\CustomPosts\FieldInterfaces\CustomPostFieldInterfaceResolver;
use PoP\Stances\ComponentConfiguration;

class CustomPostFieldResolver extends AbstractDBDataFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return [
            CustomPostFieldInterfaceResolver::class,
        ];
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'stances',
            'hasStances',
            'stanceProCount',
            'stanceNeutralCount',
            'stanceAgainstCount',
        ];
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            'stances' => TypeCastingHelpers::makeArray(SchemaDefinition::TYPE_ID),
            'hasStances' => SchemaDefinition::TYPE_BOOL,
            'stanceProCount' => SchemaDefinition::TYPE_INT,
            'stanceNeutralCount' => SchemaDefinition::TYPE_INT,
            'stanceAgainstCount' => SchemaDefinition::TYPE_INT,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function isSchemaFieldResponseNonNullable(TypeResolverInterface $typeResolver, string $fieldName): bool
    {
        $nonNullableFieldNames = [
            'stances',
            'hasStances',
            'stanceProCount',
            'stanceNeutralCount',
            'stanceAgainstCount',
        ];
        if (in_array($fieldName, $nonNullableFieldNames)) {
            return true;
        }
        return parent::isSchemaFieldResponseNonNullable($typeResolver, $fieldName);
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'stances' => $translationAPI->__('', ''),
            'hasStances' => $translationAPI->__('', ''),
            'stanceProCount' => $translationAPI->__('', ''),
            'stanceNeutralCount' => $translationAPI->__('', ''),
            'stanceAgainstCount' => $translationAPI->__('', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function resolveValue(TypeResolverInterface $typeResolver, $resultItem, string $fieldName, array $fieldArgs = [], ?array $variables = null, ?array $expressions = null, array $options = [])
    {
        $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
        $customPost = $resultItem;
        switch ($fieldName) {
            case 'stances':
                $query = array(
                    'limit' => ComponentConfiguration::getStanceListDefaultLimit(),
                    'orderby' => NameResolverFacade::getInstance()->getName('popcms:dbcolumn:orderby:posts:date'),
                    'order' => 'ASC',
                );
                \UserStance_Module_Processor_CustomSectionBlocksUtils::addDataloadqueryargsStancesaboutpost($query, $typeResolver->getID($customPost));

                return $customPostTypeAPI->getCustomPosts($query, ['return-type' => POP_RETURNTYPE_IDS]);

            case 'hasStances':
                $referencedby = $typeResolver->resolveValue($resultItem, 'stances', $variables, $expressions, $options);
                return !empty($referencedby);

            case 'stanceProCount':
            case 'stanceNeutralCount':
            case 'stanceAgainstCount':
                $cats = array(
                    'stanceProCount' => POP_USERSTANCE_TERM_STANCE_PRO,
                    'stanceNeutralCount' => POP_USERSTANCE_TERM_STANCE_NEUTRAL,
                    'stanceAgainstCount' => POP_USERSTANCE_TERM_STANCE_AGAINST,
                );

                $query = array();
                \UserStance_Module_Processor_CustomSectionBlocksUtils::addDataloadqueryargsStancesaboutpost($query, $typeResolver->getID($customPost));

                // Override the category
                $query['tax-query'][] = [
                    'taxonomy' => POP_USERSTANCE_TAXONOMY_STANCE,
                    'terms'    => $cats[$fieldName],
                ];

                // // All results
                // $query['limit'] = 0;

                return $customPostTypeAPI->getCustomPostCount($query);
        }

        return parent::resolveValue($typeResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'stances':
                return StanceTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName, $fieldArgs);
    }
}