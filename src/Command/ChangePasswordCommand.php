<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:change-password',
    description: 'Change user password',
)]
class ChangePasswordCommand extends Command
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
            ->addArgument('email', InputArgument::OPTIONAL, 'User email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'New password')
            ->setHelp('This command allows you to change a user password...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Get email
        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('User email address: ');
            $email = $helper->ask($input, $output, $question);
        }

        // Find user
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('User with email "%s" not found!', $email));
            return Command::FAILURE;
        }

        // Get new password
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('New password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $question->setValidator(function ($answer) {
                if (strlen($answer) < 6) {
                    throw new \RuntimeException('Password must be at least 6 characters long');
                }
                return $answer;
            });
            $password = $helper->ask($input, $output, $question);

            // Confirm password
            $question = new Question('Confirm password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $confirmPassword = $helper->ask($input, $output, $question);

            if ($password !== $confirmPassword) {
                $io->error('Passwords do not match!');
                return Command::FAILURE;
            }
        }

        // Update password
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->flush();

        $io->success(sprintf('Password changed successfully for user: %s', $email));

        return Command::SUCCESS;
    }
}
