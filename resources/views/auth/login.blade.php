{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="bg-white shadow-md rounded-lg px-8 py-10">
        <h2 class="text-2xl font-semibold text-center text-gray-800 mb-6">Login</h2>
        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200" />
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200" />
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="remember" class="form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2 text-gray-700 text-sm">Remember Me</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:underline">
                    Forgot password?
                </a>
            </div>

            <button type="submit"
                class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                Sign In
            </button>
        </form>
        {{-- Tombol kembali ke halaman viz --}}
        <div class="mt-6 text-center">
            <a href="{{ route('viz.index') }}"
                class="inline-block text-sm text-gray-600 hover:text-gray-800 hover:underline">
                &larr; Back to Visualisation
            </a>
        </div>
    </div>
@endsection
