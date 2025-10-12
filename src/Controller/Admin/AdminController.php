<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\Tag;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class AdminController extends AbstractDashboardController
{
    #[Route('/members', name: 'members')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        // Redirect to albums by default
        return $this->redirect($adminUrlGenerator->setController(AlbumCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Photo Portfolio')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Photos');
        yield MenuItem::linkToCrud('Albums', 'fas fa-images', Album::class);
        yield MenuItem::linkToCrud('Photos', 'fas fa-camera', Photo::class);
        yield MenuItem::linkToRoute('Bulk Upload', 'fas fa-cloud-upload-alt', 'admin_bulk_photo_upload');
        yield MenuItem::linkToCrud('Tags', 'fas fa-tags', Tag::class);

        yield MenuItem::section('User');
        yield MenuItem::linkToRoute('My Profile', 'fas fa-user', 'app_profile');

        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::section('Administration');
            yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        }

        yield MenuItem::section('');
        yield MenuItem::linkToRoute('Back to Site', 'fas fa-arrow-left', 'app_home');
        yield MenuItem::linkToLogout('Logout', 'fas fa-sign-out-alt');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->displayUserName()
            ->displayUserAvatar(false)
            ->addMenuItems([
                MenuItem::linkToRoute('My Profile', 'fa fa-user', 'app_profile'),
            ]);
    }
}
