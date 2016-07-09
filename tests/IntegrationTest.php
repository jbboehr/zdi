<?php

namespace zdi\Tests;

use zdi\Container;
use zdi\Container\ContainerBuilder as Builder;
use zdi\Container\CompileBuilder;
use zdi\Exception\DomainException;
use zdi\Exception\OutOfBoundsException;
use zdi\Param\NamedParam;
use zdi\Param\ValueParam;
use zdi\Tests\Fixture\NoArguments;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testNoArguments(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\NoArguments::class);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testNoArgumentsFactory(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->factory()
            ->build();
        $container = $builder->build();

        $this->defaultFactoryAssertions($container, Fixture\NoArguments::class);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneScalarPositionalArgument(Builder $builder)
    {
        $string = 'stringValue';
        $builder->define(Fixture\OneScalarArgument::class)
            ->param(0, 'str')
            ->build();
        $container = $builder->build();
        $container['str'] = $string;

        $this->defaultAssertions($container, Fixture\OneScalarArgument::class);
        $this->assertSame($string, $container->get(Fixture\OneScalarArgument::class)->getString());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneScalarNamedArgument(Builder $builder)
    {
        $string = 'stringValue';
        $builder->define(Fixture\OneScalarArgument::class)
            ->param('str', 'str')
            ->build();
        $container = $builder->build();
        $container['str'] = $string;

        $this->defaultAssertions($container, Fixture\OneScalarArgument::class);
        $this->assertSame($string, $container->get(Fixture\OneScalarArgument::class)->getString());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneScalarValueArgument(Builder $builder)
    {
        $strval = 'This was specified using a ValueParam';
        $builder->define(Fixture\OneScalarArgument::class)
            ->param('str', new ValueParam($strval))
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneScalarArgument::class);
        $this->assertSame($strval, $container->get(Fixture\OneScalarArgument::class)->getString());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneArrayNamedArgument(Builder $builder)
    {
        $arrval = array('foo' => 'bar', 3 => 'baz');
        $builder->define(Fixture\OneArrayArgument::class)
            ->param('arr', 'arr')
            ->build();
        $container = $builder->build();
        $container['arr'] = $arrval;

        $this->defaultAssertions($container, Fixture\OneArrayArgument::class);
        $this->assertSame($arrval, $container->get(Fixture\OneArrayArgument::class)->getArray());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneArrayValueArgument(Builder $builder)
    {
        $arrval = array('foo' => 'bar', 3 => 'baz');
        $builder->define(Fixture\OneArrayArgument::class)
            ->param('arr', new ValueParam($arrval))
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneArrayArgument::class);
        $this->assertSame($arrval, $container->get(Fixture\OneArrayArgument::class)->getArray());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneObjectArgument(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneOptionalObjectArgument(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneOptionalObjectArgument::class)
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneOptionalObjectArgument::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneOptionalObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testOneOptionalObjectArgumentUnspecified(Builder $builder)
    {
        $builder->define(Fixture\OneOptionalObjectArgument::class)
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneOptionalObjectArgument::class);
        $this->assertNull($container->get(Fixture\OneOptionalObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testContainerArgument(Builder $builder)
    {
        $builder->define(Fixture\ContainerArgument::class)
            ->build();
        $container = $builder->build();

        $obj = $container->get(Fixture\ContainerArgument::class);
        $this->assertSame($obj->getContainer(), $container);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testRecursiveObjectArguments(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->build();
        $builder->define(Fixture\OneOptionalObjectArgument::class)
            ->build();
        $builder->define(Fixture\RecursiveObjectArguments::class)
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\RecursiveObjectArguments::class);
        $this->assertInstanceOf(Fixture\OneObjectArgument::class, $container->get(Fixture\RecursiveObjectArguments::class)->getObject1());
        $this->assertInstanceOf(Fixture\OneOptionalObjectArgument::class, $container->get(Fixture\RecursiveObjectArguments::class)->getObject2());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSetter(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneSetter::class)
            ->setter('setObject')
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneSetter::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneSetter::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSetterFactory(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneSetter::class)
            ->factory()
            ->setter('setObject')
            ->build();
        $container = $builder->build();

        $this->defaultFactoryAssertions($container, Fixture\OneSetter::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneSetter::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSetterWithArgument(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->build();
        $builder->define(Fixture\OneSetter::class)
            ->setter('setObject', Fixture\NoArguments::class)
            ->build();
        $container = $builder->build();

        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneSetter::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSetterWithInvalidParam(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $builder->define(Fixture\OneInvalidSetter::class)
            ->setter('setObject')
            ->build();
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testInvalidParam(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $builder->define(Fixture\OneObjectArgument::class)
            ->param(0, new Fixture\InvalidParam())
            ->build();
        $container = $builder->build();
        $container->get(Fixture\OneObjectArgument::class);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testInvalidParam2(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $builder->define(Fixture\OneObjectArgument::class)
            ->param(0, 2343247)
            ->build();
        $container = $builder->build();
        $container->get(Fixture\OneObjectArgument::class);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testInvalidDefinition(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $definition = new Fixture\InvalidDefinition(NoArguments::class);
        $builder->add($definition);
        // Note: different builders throw at different times here
        $container = $builder->build();
        $container->get(Fixture\NoArguments::class);
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testUndefinedIndentifier(Builder $builder)
    {
        $this->setExpectedException(OutOfBoundsException::class);
        $container = $builder->build();
        $container->get('NotDefined');
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSimpleClosure(Builder $builder)
    {
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function() {
                $obj = new Fixture\NoArguments();
                return new Fixture\OneObjectArgument($obj);
            })
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSimpleClosureFactory(Builder $builder)
    {
        $builder->define(Fixture\OneObjectArgument::class)
            ->factory()
            ->using(static function() {
                $obj = new Fixture\NoArguments();
                return new Fixture\OneObjectArgument($obj);
            })
            ->build();
        $container = $builder->build();

        $this->defaultFactoryAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSimpleClosureInvalidTypeHint(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function(callable $whoops) {})
            ->build();
        $builder->build();
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSimpleClosureNoTypeHint(Builder $builder)
    {
        $this->setExpectedException(DomainException::class);
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function($whoops) {})
            ->build();
        $builder->build();
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testSimpleClosureWithContainerAccess(Builder $builder)
    {
        $builder->define(NoArguments::class)
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function(Container $container) {
                return new Fixture\OneObjectArgument($container->get(NoArguments::class));
            })
            ->build();
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testInvalidClosureParameter(Builder $builder)
    {
        $this->setExpectedException('zdi\\Exception\\DomainException');
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function(\Exception $container) {
                return new Fixture\NoArguments();
            })
            ->build();
        $builder->build();
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testTooManyClosureParameters(Builder $builder)
    {
        $this->setExpectedException('zdi\\Exception\\DomainException');
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(static function(Container $container, $somethingElse) {
                return new Fixture\NoArguments();
            })
            ->build();
        $builder->build();
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testArrayAccess(Builder $builder)
    {
        $container = $builder->build();
        $key = 'Testing ArrayAccess key';
        $val = 'Testing ArrayAccess value';
        $this->assertFalse(isset($container[$key]));
        $container[$key] = $val;
        $this->assertTrue(isset($container[$key]));
        $this->assertSame($val, $container[$key]);
        unset($container[$key]);
        $this->assertFalse(isset($container[$key]));
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testAlias(Builder $builder)
    {
        $builder->define(NoArguments::class)
            ->alias('noArgs')
            ->build();
        $container = $builder->build();
        $this->defaultAssertions($container, NoArguments::class, 'noArgs');
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testClassProvider(Builder $builder)
    {
        $builder->define(NoArguments::class)
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(Fixture\OneObjectProvider::class)
            ->build();
        $builder->define(Fixture\OneObjectProvider::class)
            ->build();
        $container = $builder->build();
        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testClassProviderFactory(Builder $builder)
    {
        $builder->define(NoArguments::class)
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->using(Fixture\OneObjectProvider::class)
            ->factory()
            ->build();
        $builder->define(Fixture\OneObjectProvider::class)
            ->build();
        $container = $builder->build();
        $this->defaultFactoryAssertions($container, Fixture\OneObjectArgument::class);
        $this->assertInstanceOf(Fixture\NoArguments::class, $container->get(Fixture\OneObjectArgument::class)->getObject());
        $this->assertNotSame($container->get(Fixture\OneObjectArgument::class), $container->get(Fixture\OneObjectArgument::class));
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testScan(Builder $builder)
    {
        $builder->define(Fixture\OneScalarArgument::class)
            ->using(static function () {
                return new Fixture\OneScalarArgument('baz');
            })
            ->build();
        $builder->blacklist(Fixture\InvalidParam::class);
        $builder->scanDirectories(array(__DIR__ . '/Fixture/'));
        $builder->scanNamespaces(array('zdi\\Tests\\Fixture\\'));
        $container = $builder->build();

        $this->defaultAssertions($container, Fixture\NoArguments::class);
        $this->defaultAssertions($container, Fixture\OneObjectArgument::class);
        $this->defaultAssertions($container, Fixture\OneOptionalObjectArgument::class);

        $this->assertFalse($container->has(Fixture\InvalidParam::class));
        $this->assertSame('baz', $container->get(Fixture\OneScalarArgument::class)->getString());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testNamedDefinition(Builder $builder)
    {
        $builder->define(Fixture\NoArguments::class)
            ->name('noArgs')
            ->build();
        $builder->define(Fixture\OneObjectArgument::class)
            ->param(0, new NamedParam('noArgs'))
            ->build();
        $container = $builder->build();

        $this->assertTrue($container->has(Fixture\OneObjectArgument::class));
        $this->assertFalse($container->has(Fixture\NoArguments::class));
        $this->assertTrue($container->has('noArgs'));

        $this->assertSame($container->get('noArgs'), $container->get(Fixture\OneObjectArgument::class)->getObject());
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testScalarDefinition(Builder $builder)
    {
        $builder->define()
            ->name('someKey')
            ->using(static function () {
                return 'someValue';
            })
            ->build();
        $container = $builder->build();

        $this->assertSame('someValue', $container->get('someKey'));
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testScalarDefinitionFromContainer(Builder $builder)
    {
        $builder->define()
            ->name('someKey')
            ->using(static function (Container $container) {
                return $container->get('someOtherKey');
            })
            ->build();
        $container = $builder->build();
        $container['someOtherKey'] = 'someOtherValue';

        $this->assertSame('someOtherValue', $container->get('someKey'));
    }

    /**
     * @param Builder $builder
     * @dataProvider containerBuilderProvider
     */
    public function testDefaultArgumentValue(Builder $builder)
    {
        $builder->define(Fixture\DefaultValueArgument::class)
            ->build();
        $container = $builder->build();
        $this->assertSame(Fixture\DefaultValueArgument::DEFAULT_VALUE, $container->get(Fixture\DefaultValueArgument::class)->getString());
    }

    private function defaultAssertions(Container $container, $class, $key = null)
    {
        $containerKey = $key ?: $class;
        $this->assertInstanceOf($class, $container->get($containerKey));
        $this->assertSame($container->get($containerKey), $container->get($containerKey));
    }

    private function defaultFactoryAssertions(Container $container, $class, $key = null)
    {
        $containerKey = $key ?: $class;
        $this->assertInstanceOf($class, $container->get($containerKey));
        $this->assertNotSame($container->get($containerKey), $container->get($containerKey));
    }

    public function containerBuilderProvider()
    {
        // Generate a temp file
        $tmpDir = __DIR__ . '/tmp/';
        $tmpFilePrefix = 'zdiContainerTest_';
        $tmpFileSuffix = '.tmp.php';
        do {
            $className = $tmpFilePrefix . base_convert(mt_rand(0, PHP_INT_MAX), 10, 36);
            $tmpFile = $tmpDir . $className . $tmpFileSuffix;
        } while( file_exists($tmpFile) );

        // Make the container builders
        $defaultContainerBuilder = new Builder();

        $compileContainerBuilder = new Builder();
        $compileContainerBuilder->ttl(0)
            ->stat(false)
            ->file($tmpFile)
            ->className('zdi\\Tests\\Gen\\' . $className)
            ;

        return array(
            array($defaultContainerBuilder),
            array($compileContainerBuilder),
        );
    }
}
