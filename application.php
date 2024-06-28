<?php

require __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/DiskusageCommand.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @var Application $application
 * */
$application = new Application();

$application->register('hi')
    ->setDescription('Say hi')
    ->addArgument('name', InputArgument::OPTIONAL)
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln('Processing something');
        $name = $input->getArgument('name') ?? 'Not Provided so you are Unknown';
        $output->writeln('Hello , '.$name);

        return Command::SUCCESS;
    });

$application->run();
