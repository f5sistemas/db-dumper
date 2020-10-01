<?php

namespace Spatie\DbDumper\Test;

use PHPUnit\Framework\TestCase;
use Spatie\DbDumper\Databases\Firebird;
use Spatie\DbDumper\Exceptions\CannotStartDump;

class FirebirdTest extends TestCase
{
    /** @test */
    public function it_provides_a_factory_method()
    {
        $this->assertInstanceOf(Firebird::class, Firebird::create());
    }

    /** @test */
    public function it_will_throw_an_exception_when_no_credentials_are_set()
    {
        $this->expectException(CannotStartDump::class);

        Firebird::create()->dumpToFile('test.sql');
    }

    /** @test */
    public function it_can_generate_a_dump_command()
    {
        $dumpCommand = Firebird::create()
            ->setDbName('dbname')
            ->setGbakPath('c:\\firebird\\bin\\')
            ->setFbkFile('dbname.fbk')
            ->setUserName('username')
            ->setPassword('password')
            ->getDumpCommand('dump.sql');

        $this->assertSame("\"c:\\firebird\\bin\\gbak\" -user username -pas password dbname dbname.fbk -y \"dump.sql\" -v", $dumpCommand);
    }
}
