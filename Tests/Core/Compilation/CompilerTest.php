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

use Da\LessBundle\Core\Compilation\Compiler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
 
class CompilerTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
    {
    	$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
        $fs = new Filesystem();
 		$fs->mkdir($rootDir.$bundlePath.'/Resources/private/less');
 		$fs->mkdir($rootDir.$bundlePath.'/Resources/private/less/test');
 		$fs->mkdir($rootDir.$bundlePath.'/Resources/private/less/test/default/bootstrap');
 		$fs->mkdir($rootDir.$bundlePath.'/Resources/private/less/test/override/bootstrap');
 		$fs->touch($rootDir.$bundlePath.'/Resources/private/less/bootstrap.less');
 		$fs->touch($rootDir.$bundlePath.'/Resources/private/less/test/bootstrap.less');
 		$fs->touch($rootDir.$bundlePath.'/Resources/private/less/test/default/bootstrap/bootstrap.less');
    }

    protected function tearDown()
    {
    	$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
    	$fs = new Filesystem();
        $fs->remove($rootDir.$bundlePath.'/Resources/private');
        $fs->remove($rootDir.$bundlePath.'/Resources/public/css/test');
    }

	private function getRootDir()
	{
		$fs = new Filesystem();
		$path = explode(DIRECTORY_SEPARATOR, __DIR__);
		$rootDir = '';
		while (!empty($path))
		{
			$configPath = implode('/', $path).'/app/config';
			if ($fs->exists($configPath))
			{
				$rootDir = implode('/', $path);
				break;
			}
			array_pop($path);
		}

		$this->assertNotEmpty($rootDir, 'the directory of a Symfony\'s project must be present to use the compiler.');

		return $rootDir;
	}

	private function getBundlePath()
	{
		return str_replace('\\', '/', substr(realpath(__DIR__.'/../../..'), strlen($this->getRootDir())));
	}

	private function getCompilationConfig()
	{
		return array
			(
				// Good configurations.
				'compilation1' => array 
					(	    	
				    	'default' => 'DaLessBundle:',
				    	'source' => 'bootstrap',
				    	'destination' => 'DaLessBundle:test/bootstrap'
				    ),
				'compilation2' => array 
					(
				    	'default' => 'DaLessBundle:test/default/bootstrap',
				    	'override' => 'DaLessBundle:test/override/bootstrap',
				    	'source' => 'bootstrap',
				    	'destination' => 'DaLessBundle:test/bootstrap/bootstrap'
				    ),
				'compilation3' => array 
					(
				    	'source' => 'DaLessBundle:test/bootstrap',
				    	'destination' => 'DaLessBundle:test/bootstrap'
				    ),
				'compilation4' => array 
					(
						'default' => 'DaLessBundle:',
				    	'destination' => 'DaLessBundle:test/bootstrap',
				    	'source' => 'DaLessBundle:test/bootstrap'
				    ),
				// Bad configurations.
				'compilation5' => array 
					(
						'default' => 'DaLessBundle:',
				    	'destination' => 'DaLessBundle:test/bootstrap',
				    ),
				'compilation6' => array 
					(
						'default' => 'DaLessBundle:',
				    	'source' => 'DaLessBundle:test/bootstrap'
				    ),
				'compilation7' => array 
					(
				    	'destination' => 'DaLessBundle:test/bootstrap',
				    	'source' => 'test/bootstrap'
				    ),
				'compilation8' => array 
					(
						'default' => 'DaLessBundle:',
				    	'destination' => 'test/bootstrap',
				    	'source' => 'DaLessBundle:test/bootstrap'
				    )
			);
	}

	private function getCompilerMock()
	{
		$appRootDir = $this->getRootDir().'/app';
		$bundles = array('DaLessBundle' => 'Da\LessBundle\DaLessBundle');
		$compilationConfig = $this->getCompilationConfig();

		return new Compiler($bundles, $appRootDir, $compilationConfig);
	}

	public function getPrepareData()
    {
    	return array_merge($this->getPrepareGoodData(), $this->getPrepareBadData());
    }

    public function getPrepareGoodData()
    {
    	return array
	    	(
	    		array('compilation1', 'bundles/daless/less/compilation1/bootstrap.less', '/Resources/public/css/test/bootstrap.css', false),
	    		array('compilation2', 'bundles/daless/less/compilation2/bootstrap.less', '/Resources/public/css/test/bootstrap/bootstrap.css', false),
	    		array('compilation3', 'bundles/daless/less/compilation3/bootstrap.less', '/Resources/public/css/test/bootstrap.css', false),
	    		array('compilation4', 'bundles/daless/less/compilation4/bootstrap.less', '/Resources/public/css/test/bootstrap.css', false)
	    	);
    }

    private function getPrepareBadData()
    {
    	return array
	    	(
	    		array('compilation5', 'bundles/daless/less/compilation5/bootstrap.less', '/Resources/public/css/test/bootstrap.css', true),
	    		array('compilation6', 'bundles/daless/less/compilation6/bootstrap.less', '/Resources/public/css/bootstrap.css', true),
	    		array('compilation7', 'bundles/daless/less/compilation7/bootstrap.less', '/Resources/public/css/test/bootstrap.css', true),
	    		array('compilation8', 'bundles/daless/less/compilation8/bootstrap.less', '/Resources/public/css/test/bootstrap.css', true)
	    	);
    }

    /**
     * @covers Da\LessBundle\Core\Compilation\Compiler::prepare
     * @covers Da\LessBundle\Core\Compilation\Compiler::cleanTemporaryDirectory
     * @dataProvider getPrepareData
     */
    public function testPrepare($compilationId, $source, $destination, $throwsException)
    {
 		$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
 		$compiler = $this->getCompilerMock();
		$fs = new Filesystem();
		$compilationConfig = $this->getCompilationConfig();
 
 		$destination = $bundlePath.$destination;
 		$expectedCompilationInfo = array
 			(
 				$compilationId => array
		 			(
	    	  			'source' => $source,
	      				'destination' => $destination
	 				)
 			);
 		if ($fs->exists($rootDir.'/web/'.$source))
 			$fs->remove($rootDir.'/web/'.$source);
 		$compilationInfo = $compiler->prepare($compilationConfig[$compilationId], $compilationId);
 		if ($throwsException)
 			$this->assertTrue(isset($compilationInfo[$compilationId]['error']), '->prepare() throws an exception if the arguments are badly formatted');
		else
		{
	 		$this->assertEquals($expectedCompilationInfo, $compilationInfo, '->prepare() returns well formatted informations for the compilation');
	 		$this->assertTrue($fs->exists($rootDir.'/web/'.$source), '->prepare() creates a temporary public directory for the compilation');
	    	
	    	$compiler->cleanTemporaryDirectory($compilationId);
	    	$this->assertFalse($fs->exists($rootDir.'/web/'.$source), '->cleanTemporaryDirectory() cleans the temporary public directory of the compilation');
	    }
    }

    /**
     * @covers Da\LessBundle\Core\Compilation\Compiler::prepareOne
     * @dataProvider getPrepareData
     */
    public function testPrepareOne($compilationId, $source, $destination, $throwsException)
    {
    	$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
 		$compiler = $this->getCompilerMock();
		$fs = new Filesystem();
 
 		$destination = $bundlePath.$destination;
 		$expectedCompilationInfo = array
 			(
 				$compilationId => array
		 			(
	    	  			'source' => $source,
	      				'destination' => $destination
	 				)
 			);
 		if ($fs->exists($rootDir.'/web/'.$source))
 			$fs->remove($rootDir.'/web/'.$source);
 		$compilationInfo = $compiler->prepareOne($compilationId);
 		if ($throwsException)
 			$this->assertTrue(isset($compilationInfo[$compilationId]['error']), '->prepareOne() throws an exception if the arguments are badly formatted');
		else
		{
	 		$this->assertEquals($expectedCompilationInfo, $compilationInfo, '->prepareOne() returns well formatted informations for the compilation');
	 		$this->assertTrue($fs->exists($rootDir.'/web/'.$source), '->prepareOne() creates a temporary public directory for the compilation');
	  		$fs->remove($rootDir.'/web/'.$source);
	  	}
    }

	/**
     * @covers Da\LessBundle\Core\Compilation\Compiler::prepareAll
     */
    public function testPrepareAll()
    {
    	$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
 		$compiler = $this->getCompilerMock();
		$fs = new Filesystem();

 		$compilationInfo = $compiler->prepareAll();

 		$data = $this->getPrepareData();
 		$expectedCompilationInfo = array();
 		foreach ($data as $compilation)
 		{
 			$compilationId = $compilation[0];
 			$source = $compilation[1];
 			$destination = $bundlePath.$compilation[2];
 			$throwsException = $compilation[3];
	 		if ($throwsException)
	 			unset($compilationInfo[$compilationId]);
	 		else
	 		{
	 			$expectedCompilationInfo[$compilationId] = array
		 			(
		      			'source' => $source,
		      			'destination' => $destination
		 			);
				$this->assertTrue($fs->exists($rootDir.'/web/'.$source), '->prepareAll() creates a temporary public directory for the compilation');
	 			$fs->remove($rootDir.'/web/'.$source);
	 		}
 		}
 		$this->assertEquals($expectedCompilationInfo, $compilationInfo, '->prepareAll() returns well formatted informations for the compilation');
    }

    /**
     * @covers Da\LessBundle\Core\Compilation\Compiler::save
     * @dataProvider getPrepareGoodData
     */
    public function testSave($compilationId, $source, $destination, $throwsException)
    {
 		$rootDir = $this->getRootDir();
    	$bundlePath = $this->getBundlePath();
 		$compiler = $this->getCompilerMock();
		$fs = new Filesystem();
		$expectedStyle = '#style{color:#fff;}';
 
 		$destination = $bundlePath.$destination;
 		$compiler->save($expectedStyle, $destination, $compilationId);
 		$this->assertTrue($fs->exists($rootDir.$destination), '->save() creates a css file result of the compilation');

 		$finder = new Finder();
 		$absoluteDestinationPathname = $rootDir.$destination;
 		$filePos = strrpos($absoluteDestinationPathname, '/');
        $destinationPath = substr($absoluteDestinationPathname, 0, $filePos);
        $destinationFilename = substr($absoluteDestinationPathname, $filePos + 1);
        $finder->files()->in($destinationPath)->name($destinationFilename);
        $style = '';
        foreach ($finder as $fileInfo) 
        {
            $file = $fileInfo->openFile('r');
            $style = $file->fgets();
            unset($file);
        }
		$this->assertEquals($expectedStyle, $style, '->save() saves the style resulting of the less compilation');
    }
}