pipeline {
    agent any
    stages {
        stage('Audit') {
            steps {
                sh 'composer audit'
            }
        }
        stage('Test') {
            steps {
                sh 'php artisan test --coverage-text --min-coverage=100'
            }
        }
    }
}
