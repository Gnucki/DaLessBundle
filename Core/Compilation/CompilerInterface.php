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

/**
 * Compiler is the interface that a class should implement to handle the 
 * compilation of less files.
 *
 * @author Thomas Prelot
 */
interface CompilerInterface
{
    /**
     * Add a provider of compilation configuration to the list of the providers.
     *
     * @param CompilationProviderInterface $compilationProvider The provider.
     */
    function addCompilationProvider(CompilationProviderInterface $compilationProvider);

    /**
     * Get the full configuration of the compilation.
     *
     * @return array The configuration of the compilation.
     */
    function getCompilationConfiguration();

	/**
     * Prepare the compilation of all the configured less files.
     *
     * @return array The informations computed for the compilation.
     */
    function prepareAll();

    /**
     * Prepare the compilation of a less file.
     *
     * @param string $compilationId The identifier of the compilation.
     *
     * @return array The informations computed for the compilation.
     */
    function prepareOne($compilationId);

    /**
     * Prepare the compilation of a less file with the given informations.
     *
     * @param string $compilationInfo The informations related to the compilation.
     * @param string $compilationId   The identifier of the compilation.
     *
     * @return array The informations computed for the compilation.
     */
    function prepare($compilationInfo, $compilationId = '_');

    /**
     * Save a css file resulting of a compilation.
     *
     * @param string $style               The style to save.
     * @param string $destinationPathname The relative (to the bundle) destination path.
     * @param string $compilationId       The identifier of the compilation.
     */
    function save($style, $destinationPathname, $compilationId = '_');

    /**
     * Clean the temporary directory used for the compilation.
     *
     * @param string $compilationId The identifier of the compilation.
     */
    function cleanTemporaryDirectory($compilationId = '_');
}