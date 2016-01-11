<?php

namespace Phoenix\Tests;

use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Tests\Database\Adapter\DummyMysqlAdapter;
use Phoenix\Tests\Migration\AlterTableMigration;
use Phoenix\Tests\Migration\AddColumnAndAddIndexExceptionsMigration;
use Phoenix\Tests\Migration\AddForeignKeyAndAddForeignKeyExceptionsMigration;
use Phoenix\Tests\Migration\CreateAndDropExceptionsMigration;
use Phoenix\Tests\Migration\CreateAndDropTableMigration;
use Phoenix\Tests\Migration\DoubleUseOfTableExceptionMigration;
use Phoenix\Tests\Migration\SimpleQueriesMigration;
use Phoenix\Tests\Migration\UseTransactionMigration;
use PHPUnit_Framework_TestCase;

class MigrationWithDummyMysqlAdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleQueries()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new SimpleQueriesMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $adapter = new DummyMysqlAdapter();
        $migration = new SimpleQueriesMigration($adapter);
        $result = $migration->rollback();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
    
    public function testCreateAndDropTable()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new CreateAndDropTableMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $adapter = new DummyMysqlAdapter();
        $migration = new CreateAndDropTableMigration($adapter);
        $result = $migration->rollback();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
    
    public function testCreateTableException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new CreateAndDropExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method create(). Use method table() first.');
        $result = $migration->migrate();
    }
    
    public function testDropTableException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new CreateAndDropExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method drop(). Use method table() first.');
        $result = $migration->rollback();
    }
    
    public function testAddColumnException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new AddColumnAndAddIndexExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method addColumn(). Use method table() first.');
        $result = $migration->migrate();
    }
    
    public function testAddIndexException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new AddColumnAndAddIndexExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method addIndex(). Use method table() first.');
        $result = $migration->rollback();
    }
    
    public function testAddForeignKey()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new AddForeignKeyAndAddForeignKeyExceptionsMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
    
    public function testAddForeignKeyException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new AddForeignKeyAndAddForeignKeyExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method addForeignKey(). Use method table() first.');
        $result = $migration->rollback();
    }
    
    public function testDoubleUseOfTableException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new DoubleUseOfTableExceptionMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method table(). Use one of methods create(), drop() first.');
        $result = $migration->migrate();
    }
    
    public function testTrippleUseOfTableException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new DoubleUseOfTableExceptionMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method table(). Use one of methods create(), drop() first.');
        $result = $migration->rollback();
    }
    
    public function testTransaction()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new UseTransactionMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $this->assertCount(4, $migration->getExecutedQueries());
        
        $this->assertContains('::start transaction', $migration->getExecutedQueries());
        $this->assertContains('::commit', $migration->getExecutedQueries());
        $this->assertNotContains('::rollback', $migration->getExecutedQueries());
        $this->assertEquals('::start transaction', $migration->getExecutedQueries()[0]);
        $this->assertEquals('::commit', array_pop($migration->getExecutedQueries()));
    }
    
    public function testRollback()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new UseTransactionMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException');
        $result = $migration->rollback();
    }
    
    public function testRollbackWithCatchedException()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new UseTransactionMigration($adapter);
        try {
            $result = $migration->rollback();
        } catch (DatabaseQueryExecuteException $e) {
            
        }
        
        $this->assertCount(5, $migration->getExecutedQueries());
        $this->assertContains('::start transaction', $migration->getExecutedQueries());
        $this->assertContains('::rollback', $migration->getExecutedQueries());
        $this->assertNotContains('::commit', $migration->getExecutedQueries());
        $this->assertEquals('::start transaction', $migration->getExecutedQueries()[0]);
    }
    
    public function testAlterTable()
    {
        $adapter = new DummyMysqlAdapter();
        $migration = new AlterTableMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $adapter = new DummyMysqlAdapter();
        $migration = new AlterTableMigration($adapter);
        $result = $migration->rollback();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
}
