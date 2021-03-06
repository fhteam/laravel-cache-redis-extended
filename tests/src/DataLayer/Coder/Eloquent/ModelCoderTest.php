<?php namespace FHTeam\LaravelRedisCache\Tests\DataLayer\Coder\Eloquent;

use FHTeam\LaravelRedisCache\DataLayer\Coder\CoderInterface;
use FHTeam\LaravelRedisCache\DataLayer\Coder\Eloquent\ModelCoder;
use FHTeam\LaravelRedisCache\DataLayer\Serializer\GenericCoderManager;
use FHTeam\LaravelRedisCache\Tests\DatabaseTestBase;
use FHTeam\LaravelRedisCache\Tests\Fixtures\Database\Models\Bear;

/**
 * Class ModelCoderTest
 *
 * @medium
 * @package FHTeam\LaravelRedisCache\Tests\DataLayer\Coder\Eloquent
 */
class ModelCoderTest extends DatabaseTestBase
{
    /**
     * @var CoderInterface
     */
    protected $coder;

    public function setUp()
    {
        parent::setUp();
        $this->coder = new ModelCoder();
        $this->coder->setCoderManager(new GenericCoderManager());
    }

    public function testEncodeModelPlain()
    {
        $bear = Bear::where('name', 'Lawly')->firstOrFail();
        $this->assertInstanceOf(Bear::class, $bear);

        $this->assertEquals($bear, $this->coder->decode($this->coder->encode($bear)));
    }

    public function testDecodeModelPlain()
    {
        $bear = Bear::where('name', 'Lawly')->firstOrFail();
        $this->assertInstanceOf(Bear::class, $bear);
        $encodedBear = $this->coder->encode($bear);
        $decodedBear = $this->coder->decode($encodedBear);
        $this->assertEquals($bear, $decodedBear);
    }
}
