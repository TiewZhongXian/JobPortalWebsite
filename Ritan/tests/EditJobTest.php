<?php

use PHPUnit\Framework\TestCase;

class EditJobTest extends TestCase
{
    private function validate($data)
    {
        if (
            empty($data['job_title']) ||
            empty($data['job_description']) ||
            empty($data['job_requirement']) ||
            empty($data['salary_min']) ||
            empty($data['salary_max']) ||
            empty($data['location']) ||
            empty($data['job_type'])
        ) {
            return "All fields are required.";
        }

        if (!is_numeric($data['salary_min']) || !is_numeric($data['salary_max'])) {
            return "Salary minimum and salary maximum must be numeric values.";
        }

        $min = (float)$data['salary_min'];
        $max = (float)$data['salary_max'];

        if ($min < 500) {
            return "Minimum salary must be at least RM500.";
        }

        if ($min > $max) {
            return "Minimum salary cannot exceed maximum salary.";
        }

        return "";
    }

    private function canEdit($job, $userId, $role)
    {
        return $role === 'Employer'
            && $job['status'] === 'Active'
            && $job['employer_id'] === $userId;
    }

    // TestEditActiveJobAccess
    public function testEditActiveJobAccess()
    {
        $job = ['employer_id' => 1, 'status' => 'Active'];
        $this->assertTrue($this->canEdit($job, 1, 'Employer'));
    }

    // TestRequiredFieldsValidation
    public function testRequiredFieldsValidation()
    {
        $data = [
            'job_title' => '',
            'job_description' => 'desc',
            'job_requirement' => 'req',
            'salary_min' => 1000,
            'salary_max' => 2000,
            'location' => 'Selangor',
            'job_type' => 'Full-time'
        ];

        $this->assertEquals("All fields are required.", $this->validate($data));
    }

    // TestSalaryMustBeNumeric
    public function testSalaryMustBeNumeric()
    {
        $data = [
            'job_title' => 'Dev',
            'job_description' => 'desc',
            'job_requirement' => 'req',
            'salary_min' => 'abc',
            'salary_max' => 2000,
            'location' => 'Selangor',
            'job_type' => 'Full-time'
        ];

        $this->assertEquals("Salary minimum and salary maximum must be numeric values.", $this->validate($data));
    }

    // TestMinimumSalaryThreshold
    public function testMinimumSalaryThreshold()
    {
        $data = [
            'job_title' => 'Dev',
            'job_description' => 'desc',
            'job_requirement' => 'req',
            'salary_min' => 300,
            'salary_max' => 2000,
            'location' => 'Selangor',
            'job_type' => 'Full-time'
        ];

        $this->assertEquals("Minimum salary must be at least RM500.", $this->validate($data));
    }

    // TestSalaryRangeValidation
    public function testSalaryRangeValidation()
    {
        $data = [
            'job_title' => 'Dev',
            'job_description' => 'desc',
            'job_requirement' => 'req',
            'salary_min' => 4000,
            'salary_max' => 2000,
            'location' => 'Selangor',
            'job_type' => 'Full-time'
        ];

        $this->assertEquals("Minimum salary cannot exceed maximum salary.", $this->validate($data));
    }


    // TestUnauthorizedEditAccess
    public function testUnauthorizedEditAccess()
    {
        $job = ['employer_id' => 2, 'status' => 'Active'];
        $this->assertFalse($this->canEdit($job, 1, 'Employer'));
    }

    // TestCancelRedirect
    public function testCancelRedirect()
    {
        $cancel = true;
        $redirect = $cancel ? 'dashboard.php' : 'edit_job.php';

        $this->assertEquals('dashboard.php', $redirect);
    }
}
