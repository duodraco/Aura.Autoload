<?php
namespace Aura\Autoload;

/**
 * Test class for Loader.
 */
class LoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function testRegister()
    {
        $autoloader = new Loader;
        $autoloader->register();
        $functions = spl_autoload_functions();
        list($object, $method) = array_pop($functions);
        $this->assertType('Aura\Autoload\Loader', $object);
        $this->assertSame('load', $method);
    }
    
    /**
     */
    public function testLoadAndLoaded()
    {
        $class = 'Aura\Autoload\MockAutoloadClass';
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        $autoloader->load($class);
        
        $classes = get_declared_classes();
        $actual = array_pop($classes);
        $this->assertSame($class, $actual);
        
        $expect = array(
            $class => __DIR__ . DIRECTORY_SEPARATOR . 'MockAutoloadClass.php',
        );
        
        $actual = $autoloader->getLoaded();
        $this->assertSame($expect, $actual);
    }
    
    public function testLoadAlreadyLoaded()
    {
        $class = 'Aura\Autoload\MockAutoloadAlready';
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        $autoloader->load($class);
        
        $this->setExpectedException('Aura\Autoload\Exception_AlreadyLoaded');
        $autoloader->load($class);
    }
    
    /**
     * @expectedException \Aura\Autoload\Exception_NotFound
     */
    public function testLoadMissing()
    {
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        $autoloader->load('Aura\Autoload\NoSuchClass');
    }
    
    /**
     * @expectedException \Aura\Autoload\Exception_NotFound
     */
    public function testLoadNotInIncludePath()
    {
        $autoloader = new Loader;
        $autoloader->load('NoSuchClass');
    }
    
    /**
     */
    public function testLoadClassWithoutNamespace()
    {
        // set a temp directory in the package
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR
             . 'tmp' . DIRECTORY_SEPARATOR
             . 'tests';
        
        @mkdir($dir, 0777, true);
        
        // add to the include path *just for this test*
        $old_include_path = ini_get('include_path');
        ini_set('include_path', $old_include_path . PATH_SEPARATOR . $dir);
        
        // write a test file to the temp location
        $code = "<?php class ClassWithoutNamespace {}";
        $file = "$dir/ClassWithoutNamespace.php";
        file_put_contents($file, $code);
        
        // set an autoloader with paths
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        
        // autoload it
        $expect = 'ClassWithoutNamespace';
        $autoloader->load($expect);
        $classes = get_declared_classes();
        $actual = array_pop($classes);
        $this->assertSame($expect, $actual);
        
        // delete the file and directory
        unlink($file);
        rmdir($dir);
        
        // reset to old include path
        ini_set('include_path', $old_include_path);
    }
    
    public function testAddPrefixAndGetPrefixes()
    {
        $autoloader = new Loader;
        $autoloader->addPrefix('Foo_', '/path/to/Foo');
        $actual = $autoloader->getPrefixes();
        $expect = array('Foo_' => array('/path/to/Foo'));
        $this->assertSame($expect, $actual);
    }
    
    public function testAddClassAndGetClasses()
    {
        $autoloader = new Loader;
        $autoloader->addClass('FooBar', '/path/to/FooBar.php');
        $actual = $autoloader->getClasses();
        $expect = array('FooBar' => '/path/to/FooBar.php');
        $this->assertSame($expect, $actual);
    }
    
    public function testLoadExactClass()
    {
        // set a temp directory in the package
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR
             . 'tmp' . DIRECTORY_SEPARATOR
             . 'tests';
        
        @mkdir($dir, 0777, true);
        
        // write a test file to the temp location
        $code = "<?php class ClassWithoutNamespaceForAddClass {}";
        $file = "$dir/ClassWithoutNamespaceForAddClass.php";
        file_put_contents($file, $code);
        
        // set an autoloader with paths
        $autoloader = new Loader;
        $expect = 'ClassWithoutNamespaceForAddClass';
        $autoloader->addClass($expect, $file);
        
        // autoload it
        $expect = 'ClassWithoutNamespaceForAddClass';
        $autoloader->load($expect);
        $classes = get_declared_classes();
        $actual = array_pop($classes);
        $this->assertSame($expect, $actual);
        
        // delete the file and directory
        unlink($file);
        rmdir($dir);
    }
    
    public function testClassToFile()
    {
        $autoloader = new Loader;
        
        $list = array(
            'Foo'                       => 'Foo.php',
            'Foo_Bar'                   => 'Foo/Bar.php',
            'foo\\Bar'                  => 'foo/Bar.php',
            'foo_bar\\Baz'              => 'foo_bar/Baz.php',
            'foo_bar\\Baz_Dib'          => 'foo_bar/Baz/Dib.php',
            'foo_bar\\baz_dib\\Zim_Gir' => 'foo_bar/baz_dib/Zim/Gir.php',
        );
        
        foreach ($list as $class => $expect) {
            $actual = $autoloader->classToFile($class);
            $expect = str_replace('/', DIRECTORY_SEPARATOR, $expect);
            $this->assertSame($expect, $actual);
        }
    }
    
    public function testGetDirs()
    {
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        
        $expect = array (
            'Aura\\Autoload\\MockAutoloadChild' => __DIR__,
            'Aura\\Autoload\\MockAutoloadClass' => __DIR__,
        );
        
        // try once for comparison
        $actual = $autoloader->getDirs('Aura\Autoload\MockAutoloadChild');
        $this->assertSame($expect, $actual);
        
        // try again for code coverage on the "already exists" block
        $actual = $autoloader->getDirs('Aura\Autoload\MockAutoloadChild');
        $this->assertSame($expect, $actual);
    }
    
    public function testGetSubdirs()
    {
        $autoloader = new Loader;
        $autoloader->addPrefix('Aura\Autoload\\', __DIR__);
        
        $expect = array (
            'Aura\\Autoload\\MockAutoloadChild' => __DIR__ . DIRECTORY_SEPARATOR . 'MockAutoloadChild',
            'Aura\\Autoload\\MockAutoloadClass' => __DIR__ . DIRECTORY_SEPARATOR . 'MockAutoloadClass',
        );
        
        // try once for comparison
        $actual = $autoloader->getSubdirs('Aura\Autoload\MockAutoloadChild');
        $this->assertSame($expect, $actual);
        
        // try again for code coverage on the "already exists" block
        $actual = $autoloader->getSubdirs('Aura\Autoload\MockAutoloadChild');
        $this->assertSame($expect, $actual);
    }
}
