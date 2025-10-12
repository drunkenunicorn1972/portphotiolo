<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
            ->addArgument('name', InputArgument::OPTIONAL, 'Full name')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addOption('admin', 'a', InputOption::VALUE_NONE, 'Create as admin user')
            ->setHelp('This command allows you to create a user...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Get email
        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Email address: ');
            $question->setValidator(function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email address');
                }
                return $answer;
            });
            $email = $helper->ask($input, $output, $question);
        }

        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('User with email "%s" already exists!', $email));
            return Command::FAILURE;
        }

        // Get name
        $name = $input->getArgument('name');
        if (!$name) {
            $question = new Question('Full name: ');
            $name = $helper->ask($input, $output, $question);
        }

        // Get password
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($answer) {
                if (strlen($answer) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters long');
                }
                return $answer;
            });
            $password = $helper->ask($input, $output, $question);
        }

        // Get role
        $isAdmin = $input->getOption('admin');
        if (!$isAdmin) {
            $question = new ChoiceQuestion(
                'Select user role',
                ['User', 'Admin'],
                0
            );
            $roleChoice = $helper->ask($input, $output, $question);
            $isAdmin = $roleChoice === 'Admin';
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        $roles = ['ROLE_USER'];
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            'User created successfully! Email: %s, Role: %s',
            $email,
            $isAdmin ? 'Admin' : 'User'
        ));

        return Command::SUCCESS;
    }
}
