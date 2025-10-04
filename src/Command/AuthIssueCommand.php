<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Security\TokenIssuer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:auth:issue', description: 'Issue access+refresh tokens for a user (QA)')]
final class AuthIssueCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenIssuer $issuer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('User not found: %s', $email));

            return Command::FAILURE;
        }

        $res = $this->issuer->issue(
            owner: $user,
            scopes: [],
            accessTtl: new \DateInterval('PT15M'),
            refreshTtl: new \DateInterval('P30D'),
        );

        $io->success('Tokens issued');
        $io->writeln(json_encode([
            'access_token' => $res['access_token'],
            'access_expires_at' => $res['access_expires_at']->format(\DateTimeInterface::ATOM),
            'refresh_token' => $res['refresh_token'],
            'refresh_expires_at' => $res['refresh_expires_at']->format(\DateTimeInterface::ATOM),
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
