import os
import tempfile
import unittest

import app as app_module


class AppliedJobsTests(unittest.TestCase):
    def setUp(self):
        self.temp_dir = tempfile.TemporaryDirectory()
        self.db_path = os.path.join(self.temp_dir.name, "test_jobs.db")

        app_module.DB_PATH = self.db_path
        app_module.init_db()

        app_module.add_applied_job(
            1, "ABC Tech Sdn Bhd", "Software Engineer Intern",
            "Kuala Lumpur", "2026-03-20", "Pending"
        )
        app_module.add_applied_job(
            1, "XYZ Solutions", "Junior Web Developer",
            "Selangor", "2026-03-22", "Reviewed"
        )
        app_module.add_applied_job(
            1, "Innovatech", "Backend Developer",
            "Penang", "2026-03-25", "Interview Scheduled"
        )
        app_module.add_applied_job(
            2, "Other User Company", "Data Analyst Intern",
            "Johor", "2026-03-21", "Pending"
        )

    def tearDown(self):
        self.temp_dir.cleanup()

    def test_returns_only_jobs_for_logged_in_job_seeker(self):
        jobs = app_module.get_applied_jobs(1)

        self.assertEqual(len(jobs), 3)
        for job in jobs:
            self.assertNotEqual(job["company_name"], "Other User Company")

    def test_returns_correct_job_details(self):
        jobs = app_module.get_applied_jobs(1)

        self.assertEqual(jobs[0]["company_name"], "Innovatech")
        self.assertEqual(jobs[0]["job_title"], "Backend Developer")
        self.assertEqual(jobs[0]["location"], "Penang")
        self.assertEqual(jobs[0]["applied_date"], "2026-03-25")
        self.assertEqual(jobs[0]["application_status"], "Interview Scheduled")

    def test_returns_jobs_in_descending_applied_date_order(self):
        jobs = app_module.get_applied_jobs(1)

        dates = [job["applied_date"] for job in jobs]
        self.assertEqual(dates, ["2026-03-25", "2026-03-22", "2026-03-20"])

    def test_returns_empty_list_when_no_applied_jobs_exist(self):
        jobs = app_module.get_applied_jobs(999)
        self.assertEqual(jobs, [])

    def test_does_not_show_other_users_jobs(self):
        jobs_user_2 = app_module.get_applied_jobs(2)

        self.assertEqual(len(jobs_user_2), 1)
        self.assertEqual(jobs_user_2[0]["company_name"], "Other User Company")
        self.assertEqual(jobs_user_2[0]["job_title"], "Data Analyst Intern")


if __name__ == "__main__":
    unittest.main()
