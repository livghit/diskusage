<?php

require __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/DiskusageCommand.php";

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\{render};

/**
 * @var Application $application
 * */
$application = new Application();

$application
    ->register("diskusage")
    ->setDescription("Shows a graph with all total usage")
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln("Processing something");
        $total_usage = "30 GB";

        render("<div class='bg-indigo-500'>Command </div>");
        render("<p class='text-indigo-500'>" . $total_usage . "</p>");

        return Command::SUCCESS;
    });

$application->run();
