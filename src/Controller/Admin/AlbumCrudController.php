<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Album')
            ->setEntityLabelInPlural('Albums')
            ->setSearchFields(['name', 'description', 'location'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('uuid')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? $value->toRfc4122() : '';
            });

        yield TextField::new('name')
            ->setRequired(true);

        yield ImageField::new('coverImage')
            ->setBasePath('uploads/albums')
            ->setUploadDir('public/uploads/albums')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);

        yield TextareaField::new('description')
            ->hideOnIndex();

        yield TextField::new('location');

        yield NumberField::new('latitude')
            ->setNumDecimals(8)
            ->hideOnIndex();

        yield NumberField::new('longitude')
            ->setNumDecimals(8)
            ->hideOnIndex();

        yield IntegerField::new('photoCount')
            ->hideOnForm();

        yield IntegerField::new('viewCount')
            ->hideOnForm();

        yield AssociationField::new('photos')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->hideOnIndex();

        yield AssociationField::new('tags')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false);

        yield ChoiceField::new('viewPrivacy')
            ->setChoices([
                'Public' => 'public',
                'Member' => 'member',
                'Friends' => 'friend',
                'Family' => 'family',
                'Private' => 'private',
            ])
            ->setRequired(true);

        // Hide user field on forms, show on detail/index
        yield AssociationField::new('user')
            ->hideOnForm();

        yield DateTimeField::new('createdAt')
            ->hideOnForm();


    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Album) {
            // Set the current user
            $entityInstance->setUser($this->getUser());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
