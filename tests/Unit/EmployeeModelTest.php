<?php

namespace Tests\Unit;

use App\Models\Employee;
use Tests\TestCase;

class EmployeeModelTest extends TestCase
{
    public function test_employee_relations_use_the_surrogate_employee_id(): void
    {
        $employee = new Employee;

        $this->assertSame('id', $employee->getKeyName());
        $this->assertSame('id', $employee->getRouteKeyName());
        $this->assertTrue($employee->getIncrementing());
        $this->assertSame('employee_id', $employee->registeredHours()->getForeignKeyName());
        $this->assertSame('employee_id', $employee->disciplinaryActions()->getForeignKeyName());
        $this->assertSame('user_id', $employee->user()->getForeignKeyName());
    }
}
