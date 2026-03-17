<?php

namespace App\Console;

use App\Console\Commands\GenerateCommand;
use App\Console\Commands\NewCommand;
use App\Console\Commands\ServeCommand;
use Illuminate\Console\Application as IlluminateConsoleApplication;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlumaApplication extends IlluminateConsoleApplication
{
    private const VERSION = '0.1.0';

    public function __construct(Container $laravel, Dispatcher $events)
    {
        parent::__construct($laravel, $events, self::VERSION);

        $this->setName('Pluma');

        $this->addPlumaCommands();

        $this->setDefaultCommand('serve');
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if ($input->hasParameterOption(['--help', '-h']) && ! $input->getFirstArgument()) {
            $input = new \Symfony\Component\Console\Input\ArrayInput(['command' => 'list']);
        }

        return parent::doRun($input, $output);
    }

    protected function bootstrap(): void
    {
        // Overwrite method to skip auto-discovered artisan bootstrappers.
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand, new ListCommand];
    }

    private function addPlumaCommands(): void
    {
        $commands = [
            ServeCommand::class => 'serve',
            NewCommand::class => 'new',
            GenerateCommand::class => 'generate',
        ];

        foreach ($commands as $class => $name) {
            $command = $this->laravel->make($class);
            $command->setLaravel($this->laravel);
            $command->setName($name);
            $this->add($command);
        }
    }
}
