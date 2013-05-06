<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\LessBundle\Core\Compilation;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Compiler is the class that handle the compilation of less files.
 *
 * @author Thomas Prelot
 */
class Compiler implements CompilerInterface
{
    /**
     * The list of the bundles.
     *
     * @var array
     */
    private $bundles;

    /**
     * The Symfony's root directory.
     *
     * @var string
     */
    private $rootDir;

    /**
     * The configuration of the compilation.
     *
     * @var string
     */
    private $compilationConfig;

    /**
     * Flag to check if the compilation has been fully loaded.
     *
     * @var string
     */
    private $isCompilationConfigFullyLoaded = false;

    /**
     * The list of the providers of compilation configuration.
     *
     * @var array
     */
    private $compilationProviders = array();

	/**
     * Constructor.
     *
     * @param array   $bundles           The list of the bundles.
     * @param string  $appRootDir        The Symfony's app directory.
     * @param string  $compilationConfig The configuration of the compilation.
     */
    public function __construct(array $bundles, $appRootDir, $compilationConfig)
    {
        $this->bundles = $bundles;
        $this->rootDir = str_replace('\\', '/', realpath($appRootDir.'/..'));
        $this->compilationConfig = $compilationConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function addCompilationProvider(CompilationProviderInterface $compilationProvider)
    {
        $this->compilationProviders[] = $compilationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompilationConfiguration()
    {
        if (!$this->isCompilationConfigFullyLoaded)
        {
            $this->isCompilationConfigFullyLoaded = true;
            foreach ($this->compilationProviders as $compilationProvider) 
            {
                $this->compilationConfig = array_merge($this->compilationConfig, $compilationProvider->getLessCompilationConfiguration());
            }
        }
        return $this->compilationConfig;
    }

	/**
     * {@inheritdoc}
     */
    public function prepareAll()
    {
        $compilation = array();

        foreach ($this->getCompilationConfiguration() as $compilationId => $compilationInfo)
        {
            try
            {
                $compilation = array_merge($compilation, $this->prepareOne($compilationId));
            }
            catch (\Exception $e)
            {
                $compilation = array_merge($compilation, array($compilationId => array('error' => $e->getMessage())));
            }
        }

        return $compilation;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareOne($compilationId)
    {
        try
        {
            $compilationConfig = $this->getCompilationConfiguration();
            if (!isset($compilationConfig[$compilationId]))
                throw new \Exception('The compilation "'.$compilationId.'" is not defined in the configuration.');
                
            $compilationInfo = $compilationConfig[$compilationId];
        }
        catch (\Exception $e)
        {
            return array($compilationId => array('error' => $e->getMessage()));
        }

        return $this->prepare($compilationInfo, $compilationId);
    }

    /**
     * Get the path of the temporary directory of a compilation.
     *
     * @param string $compilationId The identifier of the compilation.
     *
     * @return string The path of the temporary directory.
     */
    private function getTemporaryPath($compilationId)
    {
        return $this->rootDir.'/web/bundles/daless/less/'.$compilationId;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception If the bundle of the source is not defined or not loaded.
     * @throws Exception If the source of the compilation is an empty string.
     * @throws Exception If the destination of the compilation is an empty string.
     */
    public function prepare($compilationInfo, $compilationId = '_')
    {
        try
        {
            $tempPath = $this->getTemporaryPath($compilationId);

            // Format the configuration.
            $defaultPath = '';
            if (isset($compilationInfo['default']) && !empty($compilationInfo['default']))
                $defaultPath = $this->resolvePath($compilationInfo['default'], '/Resources/private/less');
            
            $overridePath = '';
            if (isset($compilationInfo['override']) && !empty($compilationInfo['override']))
                $overridePath = $this->resolvePath($compilationInfo['override'], '/Resources/private/less');

            $sourcePathname = '';
            if (isset($compilationInfo['source']) && !empty($compilationInfo['source']))
            {
                $sourcePathname = $compilationInfo['source'];
                if (strpos($sourcePathname, ':') === false && strpos($sourcePathname, '/') === false)
                {
                    if (!empty($overridePath))
                        $sourcePathname = $overridePath;
                    else
                        $sourcePathname = $defaultPath;
                    $sourcePathname .= '/'.$compilationInfo['source'];
                }
                else
                    $sourcePathname = $this->resolvePath($compilationInfo['source'], '/Resources/private/less');
                $sourcePath = '';
                $sourceFilename = $sourcePathname;
                $filePos = strrpos($sourcePathname, '/');
                if ($filePos !== false)
                {
                    $sourcePath = substr($sourcePathname, 0, $filePos);
                    $sourceFilename = substr($sourcePathname, $filePos + 1);
                }
                if (empty($defaultPath))
                {
                    if (empty($sourcePath))
                        throw new \Exception('The bundle of the source or the default field of the compilation "'.$compilationId.'" must be defined in the configuration.');
                    $defaultPath = $sourcePath;
                }
                $sourcePathname = 'bundles/daless/less/'.$compilationId.'/'.$sourceFilename;
                if (strpos($sourcePathname, '.less') === false)
                    $sourcePathname .= '.less';
            }
            else
                throw new \Exception('The source of the compilation "'.$compilationId.'" cannot be an empty string.');

            $destinationPathname = '';
            if (isset($compilationInfo['destination']) && !empty($compilationInfo['destination']))
            {
                $destinationPathname = $compilationInfo['destination'];
                if (strpos($destinationPathname, ':') === false && strpos($destinationPathname, '/') === false)
                {
                    if (!empty($overridePath))
                        $destinationPathname = $this->resolvePath($compilationInfo['override'], '/Resources/public/css');
                    else
                        $destinationPathname = $this->resolvePath($compilationInfo['default'], '/Resources/public/css');
                    $destinationPathname .= $compilationInfo['destination'];
                }
                else
                    $destinationPathname = $this->resolvePath($compilationInfo['destination'], '/Resources/public/css');
                if (strpos($destinationPathname, '.css') === false)
                    $destinationPathname .= '.css';
            }
            else
                throw new \Exception('The destination of the compilation "'.$compilationId.'" cannot be an empty string.');

            // Copy the default directory to a public zone and override it if the overriding directory
            // is defined.
            $fs = new Filesystem();
            if (!$fs->exists($tempPath))
                $fs->mkdir($tempPath);
            $fs->mirror($defaultPath, $tempPath);
            if (!empty($overridePath))
            {
                $finder = new Finder();
                $finder->files()->in($overridePath);
                foreach ($finder as $file) 
                {
                    $fs->copy($file->getRealPath(), $tempPath.'/'.$file->getFilename(), true);
                }
            }

            $destinationPathname = $fs->makePathRelative($destinationPathname, $this->rootDir);
            if (substr($destinationPathname, strlen($destinationPathname) - 1) === '/')
                $destinationPathname = substr($destinationPathname, 0, strlen($destinationPathname) - 1);
            if (substr($destinationPathname, 0, 1) !== '/')
                $destinationPathname = '/'.$destinationPathname;
        }
        catch (\Exception $e)
        {
            return array($compilationId => array('error' => $e->getMessage()));
        }

        return array($compilationId => array('source' => $sourcePathname, 'destination' => $destinationPathname));
    }

    /**
     * Resolve the path.
     *
     * The path is of the form BundleName:path.
     * In that last case, the corresponding path is {bundle_root_dir}/{$relativeDefaultPath}/path
     *
     * @param string $path                The path of the form described.
     * @param string $relativeDefaultPath The default path relative to the bundle's one.
     *
     * @return string The resolved path.
     *
     * @throws Exception If the path is not of the form BundleName:path.
     * @throws Exception If the path contain the characters "..".
     * @throws Exception If the given bundle is not part of the loaded bundles.
     */
    private function resolvePath($path, $relativeDefaultPath)
    {
        // Security: case where the feature would'nt be correctly secured.
        // You only can access few specified directories.
        if (strpos($path, ':') === false)
            throw new \Exception('For security reasons, the path "'.$path.'" should be of the form "BundleName:path" corresponding to the directory "{bundle_root_dir}'.$relativeDefaultPath.'/{path}".');
        if (strpos($path, '..') !== false)
            throw new \Exception('For security reasons, the path "'.$path.'" cannot contain the characters "..".');

        $pathParts = explode(':', $path);
        foreach ($this->bundles as $bundleName => $bundle) 
        {
            if ($pathParts[0] === $bundleName)
            {
                $reflection = new \ReflectionClass($bundle);
                $bundlePath = str_replace('\\', '/', dirname($reflection->getFilename()));
                return $bundlePath.$relativeDefaultPath.'/'.$pathParts[1];
            }
        }

        throw new \Exception('The bundle "'.$pathParts[0].'" does not exist. It could be a typo error or the bundle is not correctly loaded in the AppKernel.php file.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception If the path contains the characters "..".
     * @throws Exception If the path doest not contain "/Resources/public/css".
     */
    public function save($style, $destinationPathname, $compilationId = '_')
    {
        // Security: case where the feature would'nt be correctly secured.
        // You only can access few specified directories.
        if (strpos($destinationPathname, '..') !== false)
            throw new \Exception('For security reasons, the path "'.$destinationPathname.'" cannot contain the characters "..".');
        if (strpos($destinationPathname, '/Resources/public/css') === false)
            throw new \Exception('For security reasons, the path "'.$destinationPathname.'" must contain "/Resources/public/css".');

        $fs = new Filesystem();
        $absoluteDestinationPathname = $this->rootDir.$destinationPathname;
        $filePos = strrpos($absoluteDestinationPathname, '/');
        $destinationPath = substr($absoluteDestinationPathname, 0, $filePos);
        $destinationFilename = substr($absoluteDestinationPathname, $filePos + 1);
        if (!$fs->exists($destinationPath))
            $fs->mkdir($destinationPath);
        $fs->touch($absoluteDestinationPathname);
        
        $finder = new Finder();
        $finder->files()->in($destinationPath)->name($destinationFilename);
        // Could be long for a big file!
        set_time_limit(120);
        foreach ($finder as $fileInfo) 
        {
            $file = $fileInfo->openFile('w');
            $file->fwrite($style);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanTemporaryDirectory($compilationId = '_')
    {
        $tempPath = $this->getTemporaryPath($compilationId);

        $fs = new Filesystem();
        if ($fs->exists($tempPath))
            $fs->remove($tempPath);
    }
}