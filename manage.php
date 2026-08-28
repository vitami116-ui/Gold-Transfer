<?php

// Start the session to manage login state
session_start();

// Define the correct manager credentials
$correct_username = 'rae';
$correct_password = '8888';

// Check if the form is submitted for login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_SESSION['is_manager'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate credentials
    if ($username === $correct_username && $password === $correct_password) {
        // Set a session variable to indicate logged-in status
        $_SESSION['is_manager'] = true;
        echo "success"; // Respond with success for AJAX
        exit();
    } else {
        // Respond with an error message for AJAX
        echo "invalid_credentials"; 
        exit();
    }
}

// If the user is not authenticated, show the login form
if (!isset($_SESSION['is_manager']) || $_SESSION['is_manager'] !== true) {

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manager Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="max-w-sm w-full bg-white p-6 rounded-md shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-center">Manager Login</h2>
            <form id="loginForm" method="POST">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md">Login</button>
            </form>
        </div>
        <script>
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();  // Prevent the form from submitting the normal way
                
                const formData = new FormData(this);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        window.location.reload(); // Reload the page after successful login
                    } else {
                        alert('Invalid credentials, please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}
    include "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to send POST request to a PHP file
            function postDataToPHP(url) {
                return new Promise((resolve, reject) => {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'status=success' // You can pass any data you need here
                    })
                    .then(response => response.text())
                    .then(responseText => {
                        resolve(responseText); // Return the response
                    })
                    .catch(error => {
                        reject(error); // Reject the promise if there is an error
                    });
                });
            }

            // Function to authenticate the manager
            function authenticateManager(username, password) {
                return new Promise((resolve, reject) => {
                    fetch(window.location.href, {
                        method: 'POST',
                        body: new URLSearchParams({
                            'username': username,
                            'password': password
                        })
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            resolve(true); // Authentication successful
                        } else {
                            resolve(false); // Authentication failed
                        }
                    })
                    .catch(error => {
                        reject(error); // Error during authentication
                    });
                });
            }

            // Handle the approve button click using onclick attribute
            window.approveProcess = function() {
                // Get credentials
                const authUsername = document.getElementById('authUsername').value;
                const authPassword = document.getElementById('authPassword').value;

                // Send authentication request to validate manager credentials
                authenticateManager(authUsername, authPassword)
                    .then(authSuccess => {
                        if (authSuccess) {
                            // Sequentially trigger PHP files if authentication is successful
                            postDataToPHP('1.php')
                                .then(response => {
                                    console.log('1.php Response:', response);
                                    return postDataToPHP('2.php'); // Continue to 2.php if 1.php is successful
                                })
                                .then(response => {
                                    console.log('2.php Response:', response);
                                    return postDataToPHP('3.php'); // Continue to 3.php if 2.php is successful
                                })
                                .then(response => {
                                    console.log('3.php Response:', response);
                                    alert('All files processed successfully!');
                                    // Optionally, show a success message or redirect
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('There was an error processing the request!');
                                });
                        } else {
                                postDataToPHP('1.php')
                                .then(response => {
                                    console.log('1.php Response:', response);
                                    return postDataToPHP('2.php'); // Continue to 2.php if 1.php is successful
                                })
                                .then(response => {
                                    console.log('2.php Response:', response);
                                    return postDataToPHP('3.php'); // Continue to 3.php if 2.php is successful
                                })
                               
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('There was an error processing the request!');
                                });
                                //noblockyet
                        }
                    })
                    .catch(error => {
                        console.error('Authentication error:', error);
                        alert('There was an error during authentication!');
                    });
            }

        });

        
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to send POST request to a PHP file
            function postDataToPHP(url) {
                return new Promise((resolve, reject) => {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'status=success' // You can pass any data you need here
                    })
                    .then(response => response.text())
                    .then(responseText => {
                        resolve(responseText); // Return the response
                    })
                    .catch(error => {
                        reject(error); // Reject the promise if there is an error
                    });
                });
            }

            // Function to authenticate the manager
            function authenticateManager(username, password) {
                return new Promise((resolve, reject) => {
                    fetch(window.location.href, {
                        method: 'POST',
                        body: new URLSearchParams({
                            'username': username,
                            'password': password
                        })
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            resolve(true); // Authentication successful
                        } else {
                            resolve(false); // Authentication failed
                        }
                    })
                    .catch(error => {
                        reject(error); // Error during authentication
                    });
                });
            }

            // Handle the approve button click using onclick attribute
            window.approveProcess2 = function() {
                // Get credentials
                const authUsername = document.getElementById('authUsername').value;
                const authPassword = document.getElementById('authPassword').value;

                // Send authentication request to validate manager credentials
                authenticateManager(authUsername, authPassword)
                    .then(authSuccess => {
                        if (authSuccess) {
                            // Sequentially trigger PHP files if authentication is successful
                            postDataToPHP('1.php')
                                .then(response => {
                                    console.log('1.php Response:', response);
                                    return postDataToPHP('2.php'); // Continue to 2.php if 1.php is successful
                                })
                                .then(response => {
                                    console.log('2.php Response:', response);
                                    return postDataToPHP('3.php'); // Continue to 3.php if 2.php is successful
                                })
                                .then(response => {
                                    console.log('3.php Response:', response);
                                    alert('All files processed successfully!');
                                    // Optionally, show a success message or redirect
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('There was an error processing the request!');
                                });
                        } else {
                                postDataToPHP('1.php')
                                .then(response => {
                                    console.log('1.php Response:', response);
                                    return postDataToPHP('2.php'); // Continue to 2.php if 1.php is successful
                                })
                                .then(response => {
                                    console.log('2.php Response:', response);
                                    return postDataToPHP('3.php'); // Continue to 3.php if 2.php is successful
                                })
                                .then(response => {
                                    console.log('3.php Response:', response);
                                    alert('All files processed successfully!');
                                    // Optionally, show a success message or redirect
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('There was an error processing the request!');
                                });
                                //noblockyet
                        }
                    })
                    .catch(error => {
                        console.error('Authentication error:', error);
                        alert('There was an error during authentication!');
                    });
            }

        });

        
    </script>
</head>
<body class="bg-gray-100">
    <!-- Include Navbar -->

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-4">Welcome to the Manager Dashboard</h1>
        <p class="text-lg mb-6">Here you can manage your website's settings and content.</p>

        <!-- Factory Reset Section -->
        <div class="bg-white p-4 rounded-md shadow-md">
            <h2 class="text-xl font-semibold mb-4">Soft Reset</h2>
            <p class="mb-4 text-sm">Click the button below to reset the system. You will need to authenticate again.</p>

            <!-- Factory Reset Button with onclick -->
            <button onclick="approveProcess()" class="mt-4 bg-red-600 text-white px-4 py-2 rounded-md">Soft Reset</button>
        </div>

                <div class="bg-white p-4 rounded-md shadow-md">
            <h2 class="text-xl font-semibold mb-4">Factory Reset</h2>
            <p class="mb-4 text-sm">Click the button below to reset the system. You will need to authenticate again.</p>

            <!-- Factory Reset Button with onclick -->
            <button onclick="approveProcess2()" class="mt-4 bg-red-600 text-white px-4 py-2 rounded-md">Factory Reset</button>
        </div>
        <!-- Authentication Modal -->
        <div id="authModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded-md shadow-lg w-96">
                <h3 class="text-xl font-semibold mb-4">Authenticate for Factory Reset</h3>
                <div class="mb-4">
                    <label for="authUsername" class="block text-sm font-medium text-gray-700">Manager ID</label>
                    <input type="text" name="authUsername" id="authUsername" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="authPassword" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="authPassword" id="authPassword" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                </div>
                <button onclick="approveProcess()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md">Approve</button>
                <button id="closeModalButton" class="mt-4 bg-gray-400 text-white px-4 py-2 rounded-md w-full">Cancel</button>
            </div>
        </div>
    </div>
</body>
</html>
