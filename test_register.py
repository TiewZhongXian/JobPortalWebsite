import os
import unittest
import requests
import random
import string
from urllib.parse import urljoin


class RegisterTests(unittest.TestCase):
    # Local default for GitHub Actions or PHP built-in server.
    # You can override this in CI with:
    # REGISTER_URL=http://127.0.0.1:8000/register.php
    BASE_URL = os.getenv("REGISTER_URL", "http://127.0.0.1:8000/register.php")

    def setUp(self):
        self.session = requests.Session()

        rand = ''.join(random.choices(string.ascii_lowercase + string.digits, k=6))
        self.valid_data = {
            "name": "Test User",
            "email": f"user_{rand}@example.com",
            "phone": "01" + ''.join(random.choices(string.digits, k=8)),  # 10 digits total
            "password": "Password123",
            "confirm_password": "Password123"
        }

    def tearDown(self):
        self.session.close()

    def submit_and_follow(self, data):
        """
        Submit the registration form and manually follow the redirect.
        This matches the PHP Post-Redirect-Get flow.
        """
        response = self.session.post(
            self.BASE_URL,
            data=data,
            allow_redirects=False,
            timeout=10
        )

        self.assertEqual(
            response.status_code, 302,
            f"Expected redirect after POST, got {response.status_code}. Response body: {response.text}"
        )

        location = response.headers.get("Location", "")
        self.assertTrue(location, "Expected Location header after POST redirect.")

        final_url = urljoin(self.BASE_URL, location)
        final_response = self.session.get(final_url, timeout=10)

        self.assertEqual(
            final_response.status_code, 200,
            f"Expected 200 after redirect, got {final_response.status_code}"
        )

        return final_response.text

    def assertContainsMessage(self, html, expected_message):
        self.assertIn(
            expected_message,
            html,
            f"Expected message not found.\nExpected: {expected_message}\nHTML:\n{html}"
        )

    def test_blank_fields_rejected(self):
        data = {
            "name": "",
            "email": "",
            "phone": "",
            "password": "",
            "confirm_password": ""
        }
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: All fields are required."
        )

    def test_invalid_email_rejected(self):
        data = self.valid_data.copy()
        data["email"] = "invalid-email-format"
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: Invalid email format."
        )

    def test_phone_with_letters_rejected(self):
        data = self.valid_data.copy()
        data["phone"] = "01234ABCD9"
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: Phone number must contain only numbers (10-11 digits)."
        )

    def test_phone_too_short_rejected(self):
        data = self.valid_data.copy()
        data["phone"] = "012345678"  # 9 digits
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: Phone number must contain only numbers (10-11 digits)."
        )

    def test_phone_too_long_rejected(self):
        data = self.valid_data.copy()
        data["phone"] = "012345678901"  # 12 digits
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: Phone number must contain only numbers (10-11 digits)."
        )

    def test_password_mismatch_rejected(self):
        data = self.valid_data.copy()
        data["confirm_password"] = "DifferentPassword123"
        html = self.submit_and_follow(data)
        self.assertContainsMessage(
            html,
            "Registration failed: Passwords do not match."
        )

    def test_duplicate_email_rejected(self):
        first_user = self.valid_data.copy()
        self.submit_and_follow(first_user)

        second_user = self.valid_data.copy()
        second_user["phone"] = "01" + ''.join(random.choices(string.digits, k=8))
        html = self.submit_and_follow(second_user)

        self.assertContainsMessage(
            html,
            "Registration failed: This email is already registered."
        )

    def test_duplicate_phone_rejected(self):
        shared_phone = "01" + ''.join(random.choices(string.digits, k=8))

        first_user = self.valid_data.copy()
        first_user["email"] = f"first_{''.join(random.choices(string.ascii_lowercase, k=5))}@example.com"
        first_user["phone"] = shared_phone
        self.submit_and_follow(first_user)

        second_user = self.valid_data.copy()
        second_user["email"] = f"second_{''.join(random.choices(string.ascii_lowercase, k=5))}@example.com"
        second_user["phone"] = shared_phone
        html = self.submit_and_follow(second_user)

        self.assertContainsMessage(
            html,
            "Registration failed: This phone number is already registered."
        )

    def test_successful_registration(self):
        html = self.submit_and_follow(self.valid_data)
        self.assertContainsMessage(
            html,
            f"Account successfully created! Welcome to the Job Portal, {self.valid_data['name']}."
        )
        self.assertIn("Proceed to Login Page", html)


if __name__ == "__main__":
    unittest.main(verbosity=2)
