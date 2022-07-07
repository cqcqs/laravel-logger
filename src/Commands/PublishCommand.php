<?php

namespace Cqcqs\Logger\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\MountManager;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logger:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->publish();
        $this->clean();
    }

    /**
     * @return void
     */
    protected function publish()
    {
        $publishes = ServiceProvider::pathsToPublish(null, 'cqcqs-logger-config');
        foreach ($publishes as $from => $to) {
            $this->publishItem($from, $to);
        }
    }

    /**
     * @param $from
     * @param $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }
    }

    /**
     * @param $from
     * @param $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if (! $this->files->exists($to)) {
            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    protected function publishDirectory($from, $to)
    {
        $localClass = class_exists(LocalAdapter::class) ? LocalAdapter::class : LocalFilesystemAdapter::class;

        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new $localClass($from)),
            'to' => new Flysystem(new $localClass($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    protected function moveManagedFiles(MountManager $manager)
    {
        if (method_exists($manager, 'put')) {
            foreach ($manager->listContents('from://', true) as $file) {
                if (
                    $file['type'] === 'file'
                    && ! $manager->has('to://'.$file['path'])
                    && ! $this->isExceptPath($manager, $file['path'])
                ) {
                    $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
                }
            }

            return;
        }

        foreach ($manager->listContents('from://', true) as $file) {
            $path = Str::after($file['path'], 'from://');

            if ($file['type'] === 'file' && (! $manager->fileExists('to://'.$path))) {
                $manager->write('to://'.$path, $manager->read($file['path']));
            }
        }
    }

    protected function isExceptPath($manager, $path)
    {
        return $manager->has('to://'.$path) && Str::contains($path, ['/menu.php', '/global.php']);
    }

    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }

    protected function clean()
    {
        $this->call('config:clear');
        $this->call('cache:clear');
    }

}
