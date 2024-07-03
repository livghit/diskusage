<?php

require __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/DiskusageCommand.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\{render};

/**
 * @var Application $application
 * */
$application = new Application();

function cpu(): int
{
    return match (PHP_OS_FAMILY) {
        'Darwin' => (int) `top -l 1 | grep -E "^CPU" | tail -1 | awk '{ print $3 + $5 }'`,
        'Linux' => (int) `top -bn1 | grep -E '^(%Cpu|CPU)' | awk '{ print $2 + $4 }'`,
        'Windows' => (int) trim(`wmic cpu get loadpercentage | more +1`),
        'BSD' => (int) `top -b -d 2| grep 'CPU: ' | tail -1 | awk '{print$10}' | grep -Eo '[0-9]+\.[0-9]+' | awk '{ print 100 - $1 }'`,
        default => throw new RuntimeException(
            'The pulse:check command does not currently support '.
                PHP_OS_FAMILY
        ),
    };
}

function memory(): array
{
    $memoryTotal = match (PHP_OS_FAMILY) {
        'Darwin' => intval(
            `sysctl hw.memsize | grep -Eo '[0-9]+'` / 1024 / 1024
        ),
        'Linux' => intval(
            `cat /proc/meminfo | grep MemTotal | grep -E -o '[0-9]+'` / 1024
        ),
        'Windows' => intval(
            ((int) trim(
                `wmic ComputerSystem get TotalPhysicalMemory | more +1`
            )) /
                1024 /
                1024
        ),
        'BSD' => intval(`sysctl hw.physmem | grep -Eo '[0-9]+'` / 1024 / 1024),
        default => throw new RuntimeException(
            'The command does not currently support '.PHP_OS_FAMILY
        ),
    };

    $memoryUsed = match (PHP_OS_FAMILY) {
        'Darwin' => $memoryTotal -
            intval(
                (intval(`vm_stat | grep 'Pages free' | grep -Eo '[0-9]+'`) *
                    intval(`pagesize`)) /
                    1024 /
                    1024
            ), // MB
        'Linux' => $memoryTotal -
            intval(
                `cat /proc/meminfo | grep MemAvailable | grep -E -o '[0-9]+'` /
                    1024
            ), // MB
        'Windows' => $memoryTotal -
            intval(
                ((int) trim(`wmic OS get FreePhysicalMemory | more +1`)) / 1024
            ), // MB
        'BSD' => intval(
            (intval(
                `( sysctl vm.stats.vm.v_cache_count | grep -Eo '[0-9]+' ; sysctl vm.stats.vm.v_inactive_count | grep -Eo '[0-9]+' ; sysctl vm.stats.vm.v_active_count | grep -Eo '[0-9]+' ) | awk '{s+=$1} END {print s}'`
            ) *
                intval(`pagesize`)) /
                1024 /
                1024
        ), // MB
        default => throw new RuntimeException(
            'The command does not currently support '.PHP_OS_FAMILY
        ),
    };

    return [
        'total' => $memoryTotal,
        'used' => $memoryUsed,
    ];
}

function storage()
{
    return ['storage' => collect(['./'])
        ->map(fn (string $directory) => [
            'directory' => $directory,
            'total' => $total = intval(round(disk_total_space($directory) / 1024 / 1024)), // MB
            'used' => intval(round($total - (disk_free_space($directory) / 1024 / 1024))), // MB
        ])
        ->all()];
}

$application
    ->register('diskusage')
    ->setDescription('Shows a graph with all total usage')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln('Processing something');
        $memory = memory();
        $memoryTotal = $memory['total'];
        $memoryUsed = $memory['used'];
        $storage = storage();
        $storageTotalGB = round($storage['storage'][0]['total'] / 1000);
        $storageUsedGB = round($storage['storage'][0]['used'] / 1000);

        $cpuUsage = cpu();

        render("<div class='bg-indigo-500'>Command </div>");
        render(
            "<p class='text-indigo-500'>".$storageUsedGB." / ".$storageTotalGB." GB</p>"
        );

        return Command::SUCCESS;
    });

$application->run();
