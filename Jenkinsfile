pipeline {
    agent any

    options { timestamps() }

    environment {
        COMPOSER_ALLOW_SUPERUSER = '1'
        XDEBUG_MODE = 'coverage'
    }

    stages {
        stage('Install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --no-progress'
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Security audit') {
            steps {
                // exit code != 0 bila ada kerentanan -> pipeline GAGAL
                sh 'composer audit'
            }
        }

        stage('Build assets') {
            when { expression { fileExists('package.json') } }
            steps {
                sh 'npm ci --ignore-scripts || npm install --ignore-scripts'
                sh 'npm run build'
            }
        }

        stage('Test + coverage gate') {
            steps {
                sh '''
                    vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml --log-junit=junit.xml | tee coverage.txt
                    # Gate: coverage app/ harus 100% (statements, methods, classes)
                    php -r '
                      $m = simplexml_load_file("coverage.xml")->project->metrics;
                      $pairs = [["statements","coveredstatements"],["methods","coveredmethods"],["classes","coveredclasses"]];
                      $bad = 0;
                      foreach ($pairs as [$t,$c]) {
                        if ((int)$m[$t] !== (int)$m[$c]) { fwrite(STDERR, "Coverage $t: {$m[$c]}/{$m[$t]}\\n"); $bad = 1; }
                      }
                      exit($bad);
                    '
                '''
            }
            post {
                always { junit allowEmptyResults: true, testResults: 'junit.xml' }
            }
        }

        stage('Trivy scan') {
            steps {
                sh 'trivy fs --exit-code 0 --severity HIGH,CRITICAL . || true'
            }
        }

        stage('Package') {
            steps {
                sh 'zip -r latest-app.zip . -x "vendor/*" "node_modules/*" ".git/*" ".env" "coverage*" "junit.xml" ".phpunit.cache/*"'
                archiveArtifacts artifacts: 'latest-app.zip', fingerprint: true
            }
        }
    }
}
