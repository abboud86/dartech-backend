<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:log-demo', description: 'Emit a log entry for testing Monolog')]
final class LogDemoCommand extends Command
{
    public function __construct(private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('message', InputArgument::REQUIRED)
            ->addOption('level', null, InputOption::VALUE_REQUIRED, 'PSR-3 level', 'info')
            ->addOption('context', null, InputOption::VALUE_REQUIRED, 'JSON context', '{}');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = (string) $input->getArgument('message');
        $level = strtolower((string) $input->getOption('level'));
        $contextJson = (string) $input->getOption('context');
        $context = json_decode($contextJson, true);
        if (!is_array($context)) {
            $context = ['raw' => $contextJson];
        }

        $levels = ['debug','info','notice','warning','error','critical','alert','emergency'];
        if (!in_array($level, $levels, true)) {
            $level = 'info';
        }

        $this->logger->log($level, $message, $context);
        $output->writeln('logged');

        return Command::SUCCESS;
    }
}
