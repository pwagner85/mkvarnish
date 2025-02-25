<?php

namespace DMK\Mkvarnish\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use DMK\Mkvarnish\Tests\Unit\MkVarnishBaseTest;
use DMK\Mkvarnish\Utility\Configuration;

/**
 * This class communicates with the varnish server.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class ConfigurationTestCase extends MkVarnishBaseTest
{
    /**
     * @var array
     */
    protected $extConfBackup = [];

    /**
     * Set up the Test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->extConfBackup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('mkvarnish');
        parent::setUp();
    }

    /**
     * Tear down the Test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->setExtensionConfiguration($this->extConfBackup);
        parent::tearDown();
    }

    /**
     * Test the getExtConfValue method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetExtConfValue()
    {
        $this->setExtensionConfiguration(['my_key' => 'my_value']);

        $mock = $this->getMockBuilder(Configuration::class)
            ->setMethods(['dummy'])
            ->getMock();

        // should return right value
        $this->assertEquals(
            'my_value',
            $this->callInaccessibleMethod($mock, 'getExtConfValue', 'my_key')
        );
        // should return null if there is no value
        $this->assertEquals(
            null,
            $this->callInaccessibleMethod($mock, 'getExtConfValue', 'no_key')
        );
    }

    /**
     * Test the isSendCacheHeadersEnabled method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledChecksReverseProxy()
    {
        $this->setExtensionConfiguration(['sendCacheHeaders' => '0']);

        $mock = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isRevProxy'])
            ->getMock();

        $mock->expects($this->once())->method('isRevProxy')->will($this->returnValue('rp'));

        // should return rp
        $this->assertEquals(
            'rp',
            $mock->isSendCacheHeadersEnabled()
        );
    }

    /**
     * Test the isSendCacheHeadersEnabled method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledShouldReturnTrue()
    {
        $this->setExtensionConfiguration(['sendCacheHeaders' => '1']);

        $mock = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isRevProxy'])
            ->getMock();
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertTrue($mock->isSendCacheHeadersEnabled());
    }

    /**
     * Test the isSendCacheHeadersEnabled method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testIsSendCacheHeadersEnabledShouldReturnFalse()
    {
        $this->setExtensionConfiguration(['sendCacheHeaders' => '2']);

        $mock = $this->getMockBuilder(Configuration::class)
            ->setMethods(['isRevProxy'])
            ->getMock();
        $mock->expects($this->never())->method('isRevProxy');

        // should return rp
        $this->assertFalse($mock->isSendCacheHeadersEnabled());
    }

    /**
     * Test the getHostNamesForPurge method.
     *
     * @return void
     *
     * @group unit
     * @test
     */
    public function testGetHostNamesForPurgeIfConfigured()
    {
        $this->setExtensionConfiguration(['hostnames' => '127.0.0.1, 192.168.0.1']);

        $mock = new Configuration();

        $hostnames = $mock->getHostNamesForPurge();

        $this->assertCount(2, $hostnames);
        $this->assertEquals('127.0.0.1', $hostnames[0]);
        $this->assertEquals('192.168.0.1', $hostnames[1]);
    }

    /**
     * @return void
     * @test
     */
    public function testGetHostNamesForPurgeIfNoneConfigured()
    {
        $this->setExtensionConfiguration(['hostnames' => '']);

        $mock = new Configuration();

        $hostnames = $mock->getHostNamesForPurge();
        $httpHost = '';
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $httpHost = (string) $_SERVER['HTTP_HOST'];
        }

        $this->assertCount(1, $hostnames);
        $this->assertEquals($httpHost, $hostnames[0]);
    }
}
