<?php

/*
 * This file is part of the Da Project.
 *
 * (c) Thomas Prelot <tprelot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Da\LessBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/__da/less")
 */
class MainController extends ContainerAware
{
	/**
	 * Display the interface of the compilation.
	 *
     * @Route("")
     * @Template()
     */
    public function indexAction()
    {
    	if (!$this->isGranted())
    		throw new AccessDeniedException();

    	$request = $this->container->get('request');

		$form = $this->container->get('form.factory')->createBuilder()
			->add('default', 'text', array('label' => 'Default directory of the less source files (ex: "MyBundle:default") (optional)', 'required' => false, 'attr' => array('size' => 40)))
			->add('override', 'text', array('label' => 'Directory containing the files overriding the default ones (ex: "MyBundle:override") (optional)', 'required' => false, 'attr' => array('size' => 40)))
			->add('source', 'text', array('label' => 'The source file of the compilation (ex: "mylessfile" or "MyBundle:mylessfile" if the default directory is not defined)', 'required' => true, 'attr' => array('size' => 40)))
			->add('destination', 'text', array('label' => 'The destination css file (ex: "MyBundle:mystyle")', 'required' => true, 'attr' => array('size' => 40)))
			->getForm();

		if ($request->isMethod('POST')) 
		{
			$form->bind($request);

			if ($form->isValid()) 
			{
				$data = $form->getData();
				return new RedirectResponse($this->container->get('router')->generate('da_less_main_compile', $data), 302);
			}
		}

		$compilation = $this->container->get('da.less.compiler')->getCompilationConfiguration();

		return array('form' => $form->createView(), 'compilation' => $compilation);
    }

    /**
     * Get all the informations needed for the compilation in the
     * client browser thanks to the less.js.
     * 
     * @Route("/compile/{compilationId}", defaults={"compilationId"=""})
     * @Template()
     */
    public function compileAction($compilationId)
    {
    	if (!$this->isGranted())
    		throw new AccessDeniedException();

    	$compilation = array();

    	if (empty($compilationId))
    	{
    		$queryParameters = $this->container->get('request')->query;
			$compilationInfo = array
	        	(
	        		'default' => $queryParameters->container->get('default'), 
	        		'override' => $queryParameters->container->get('override'),
	        		'source' => $queryParameters->container->get('source'), 
	        		'destination' => $queryParameters->container->get('destination')
	        	);
	        $compilation = $this->container->get('da.less.compiler')->prepare($compilationInfo);
    	}
    	else if ($compilationId === '_all')
    		$compilation = $this->container->get('da.less.compiler')->prepareAll();
    	else
    		$compilation = $this->container->get('da.less.compiler')->prepareOne($compilationId);

        return array('compilation' => $compilation);
    }

    /**
     * Save the css compiled file and clean the temporary directory created during the compilation.
     *
     * @Route("/save")
     * @Template()
     */
    public function saveAction()
    {
    	if (!$this->isGranted())
    		throw new AccessDeniedException();

    	// Saving of the css file.
		try
		{
			$requestParameters = $this->container->get('request')->request;
			$compilationId = $requestParameters->get('compilationId');
			$destination = $requestParameters->get('destination');
 		   	$style = $requestParameters->get('style');
			$this->container->get('da.less.compiler')->save($style, $destination, $compilationId);
			$message = 'The compilation "'.$compilationId.'" succeeded.';
		}
		catch (\Exception $e) 
		{
			if (isset($compilationId) && is_string($compilationId))
				$message = 'The compilation "'.$compilationId.'" failed: '.$e->getMessage();
			else
				$message = 'The compilation failed: '.$e->getMessage();
		}

		// Cleaning of the temporary directory.
		try
		{
			$this->container->get('da.less.compiler')->cleanTemporaryDirectory($compilationId);
		}
		catch (\Exception $e)
		{
			$message .= ' The cleaning of the temporary directory "bundles/daless/less/'.$compilationId.'" failed. You should do it manually.';
		}

        return array('message' => $message);
    }

	/**
     * Check if the user has the right to access the less features.
     *
     * @return boolean True if the user is allowed to access the features, otherwise false.
     */
    private function isGranted()
    {
    	$security = $this->container->get('security.context');
    	$roles = $this->container->getParameter('da.less.config.roles');
    	if (!empty($roles) && strtolower($roles[0]) === 'anonymous')
			return true;
        $user = $security->getToken()->getUser();
    	foreach ($roles as $role)
    	{
    		if ($security->isGranted($role) || $user->hasRole($role)) 
	    		return true;
    	}
    	return false;
    }
}
