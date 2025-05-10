@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Settings</h1>
            <p class="text-gray-600 dark:text-gray-400">Customize your account preferences and notification settings</p>
        </div>

        <!-- Settings Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Settings -->
            <div class="lg:col-span-2">
                <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Settings</h2>
                    </div>
                    <form action="{{ route('profile.update') }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" name="name" id="name" value="{{ $user->name }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="email" value="{{ $user->email }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Change -->
                <div class="mt-8 bg-gray dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                    </div>
                    <form action="{{ route('password.update') }}" method="POST" class="p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                            <input type="password" name="password" id="password"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preferences -->
            <div>
                <div class="bg-gray dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Preferences</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Theme Toggle -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dark Mode</span>
                            <button type="button" id="theme-toggle"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 bg-gray-200 dark:bg-blue-600">
                                <span class="sr-only">Toggle dark mode</span>
                                <span aria-hidden="true"
                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-gray shadow ring-0 transition duration-200 ease-in-out translate-x-0 dark:translate-x-5"></span>
                            </button>
                        </div>

                        <!-- Notification Settings -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Notifications</h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="email_notifications" name="email_notifications" type="checkbox"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </div>
                                    <div class="ml-3">
                                        <label for="email_notifications" class="text-sm text-gray-700 dark:text-gray-300">Email Notifications</label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Receive email updates about your meetings and focus stats</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="focus_alerts" name="focus_alerts" type="checkbox"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </div>
                                    <div class="ml-3">
                                        <label for="focus_alerts" class="text-sm text-gray-700 dark:text-gray-300">Focus Alerts</label>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Get notified when your focus level drops during meetings</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="mt-8 bg-gray dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Actions</h2>
                        <form action="{{ route('profile.destroy') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                                Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    
    // Check initial theme
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        themeToggle.setAttribute('aria-checked', 'true');
    } else {
        document.documentElement.classList.remove('dark');
        themeToggle.setAttribute('aria-checked', 'false');
    }

    // Toggle theme
    themeToggle.addEventListener('click', function() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.theme = isDark ? 'dark' : 'light';
        themeToggle.setAttribute('aria-checked', isDark);
    });
});
</script>
@endpush
@endsection 