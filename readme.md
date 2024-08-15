# Spava | Coming Soon

Welcome to the Spava project! This repository is for the "Coming Soon" page of Spava, a comprehensive student study solutions platform.

## Project Overview

Spava is an all-in-one student study solution designed to provide dynamic study materials, detailed answers, interactive quizzes, and a supportive community. The "Coming Soon" page is a temporary landing page to inform visitors about the upcoming launch of Spava and collect email addresses for early access.

## Features

- **Responsive Design**: The page is designed to be mobile-friendly and responsive.
- **Form Submission**: Collects user names, email addresses, and additional information.
- **reCAPTCHA**: Integrated Google reCAPTCHA v3 to prevent spam.
- **CSRF Protection**: Implements CSRF protection to secure form submissions.
- **Rate Limiting**: Limits the number of submissions per hour to prevent abuse.

## Setup

To get started with this project locally, follow these steps:

1. **Clone the Repository**:

   ```sh
   git clone https://github.com/Akshayffb/Coming-soon.git
   cd Coming-soon
   ```

2. **Install Dependencies**:

   ```sh
   composer install
   ```

3. **Run the app**:

   ```sh
   php -S localhost:8000 -t public
   ```
