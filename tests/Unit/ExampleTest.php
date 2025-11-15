<?php

namespace MonBudget\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test simple pour vérifier que PHPUnit fonctionne
 */
class ExampleTest extends TestCase
{
    /**
     * Test basique
     */
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(4, 2 + 2);
    }

    /**
     * Test avec tableaux
     */
    public function testArrays(): void
    {
        $array = ['nom' => 'MonBudget', 'version' => '2.0'];
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('nom', $array);
        $this->assertEquals('MonBudget', $array['nom']);
    }

    /**
     * Test avec strings
     */
    public function testStrings(): void
    {
        $string = 'MonBudget V2';
        
        $this->assertIsString($string);
        $this->assertStringContainsString('MonBudget', $string);
        $this->assertStringStartsWith('Mon', $string);
    }
}
