<?php

namespace App\Controller\Admin\Filter;

use App\Form\Type\Admin\UserRolesFilterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use function count;

class UserRolesFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(UserRolesFilterType::class);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();

        $useQuotes = Types::SIMPLE_ARRAY === $fieldDto->getDoctrineMetadata()->get('type');

        if (!(null === $value || [] === $value)) {
            $orX = new Orx();
            foreach ($value as $key => $item) {
                // TODO: check this code because the loop variable is not used
                $itemParameterName = sprintf('%s_%s', $parameterName, $key);
                $orX->add(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $itemParameterName));
                $queryBuilder->setParameter($itemParameterName, $useQuotes ? '%"'.$item.'"%' : '%'.$item.'%');
            }
            $queryBuilder->andWhere($orX);
        }
    }
}