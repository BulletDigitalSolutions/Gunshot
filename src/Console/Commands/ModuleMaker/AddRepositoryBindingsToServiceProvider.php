<?php

namespace BulletDigitalSolutions\Gunshot\Console\Commands\ModuleMaker;

use BulletDigitalSolutions\Gunshot\Console\Commands\ModuleMaker\Traits\FileChangeHelpers;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AddRepositoryBindingsToServiceProvider extends Command
{
    use FileChangeHelpers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:config:repository:bind {repository} {contract} {entity} {isSearchable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bind repository to the AppServiceProvider';

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var String
     */
    protected $serviceProvider = 'AppServiceProvider';

    /**
     * @var String
     */
    protected $searchStart = 'protected $repoBindings = [';

    /**
     * @var String
     */
    protected $searchEnd = '];';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        parent::__construct();
    }

    public function handle()
    {
        if ($this->argument('isSearchable') == 'true') {
            $this->searchStart = 'protected $searchableRepoBindings = [';
        }

        $file = $this->getServiceProviderFile();
        $bindingArray = $this->getArrayFromFile($file);
        $bindingArray = $this->addNewRowToArray($bindingArray);
        $bindingArray = $this->alphabetiseArray($bindingArray);
        $bindingArray = $this->convertArrayBackToString($bindingArray);

        $file = $this->replaceTextInFile($file, $bindingArray);

        $file = $this->addImports($file, [
            $this->getNamespace($this->argument('contract')),
            $this->getNamespace($this->argument('repository')),
            sprintf('App\Entities\%s', $this->argument('entity')),
        ]);

        $this->saveFile($file, $this->getServiceProviderPath());
    }

    /**
     * @return string
     */
    public function getNewRow()
    {
        return sprintf('[%s::class, %s::class, %s::class],',
            $this->getFilenameFromPath($this->argument('contract')),
            $this->getFilenameFromPath($this->argument('repository')),
            $this->getFilenameFromPath($this->argument('entity'))
        );
    }
}
