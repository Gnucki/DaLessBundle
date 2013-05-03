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
 * CompilationProviderInterface is the interface that a class should implement
 * to provide some compilation configuration to the DaLessBundle.
 *
 * @author Thomas Prelot
 */
interface CompilationProviderInterface
{
	/**
     * Get the configuration for the less compilation.
     *
     * The configuration must be of that form:
     * <code>
     * compilation => array(
     *     {compilation_id} => array(
     *         default => {default_less_directory},
     *         override => {override_less_directory},
     *         source => {source_less_filename},
     *         destination => {destination_css_filename}));
     * </code>
     *
     * @return array The configuration for the less compilation.
     */
	function getLessCompilationConfiguration();
}