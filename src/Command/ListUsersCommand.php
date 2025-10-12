<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:list',
    description: 'List all users',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('admin', 'a', InputOption::VALUE_NONE, 'Show only admin users')
            ->setHelp('This command lists all users in the system...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->warning('No users found in the system.');
            return Command::SUCCESS;
        }

        $showOnlyAdmins = $input->getOption('admin');

        $rows = [];
        foreach ($users as $user) {
            $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

            if ($showOnlyAdmins && !$isAdmin) {
                continue;
            }

            $rows[] = [
                $user->getId(),
                $user->getName(),
                $user->getEmail(),
                implode(', ', array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_USER')),
                $user->isVerified() ? 'Yes' : 'No',
            ];
        }

        if (empty($rows)) {
            $io->warning('No admin users found in the system.');
            return Command::SUCCESS;
        }

        $io->title($showOnlyAdmins ? 'Admin Users' : 'All Users');
        $io->table(
            ['ID', 'Name', 'Email', 'Extra Roles', 'Verified'],
            $rows
        );

        $io->success(sprintf('Total users: %d', count($rows)));

        return Command::SUCCESS;
    }
}
