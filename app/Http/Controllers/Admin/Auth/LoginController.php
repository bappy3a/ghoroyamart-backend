<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FailedLoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(){
        return view('auth.login');
    }

    public function loginProcess(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $ipAddress = $request->ip();

        // Check IP-based blocking first
        $ipAttempt = FailedLoginAttempt::where('ip_address', $ipAddress)->first();

        // Clear expired IP lockout
        if($ipAttempt && $ipAttempt->locked_until && $ipAttempt->locked_until->isPast()){
            $ipAttempt->update([
                'locked_until' => null,
                'attempts' => 0,
            ]);
            $ipAttempt = FailedLoginAttempt::where('ip_address', $ipAddress)->first();
        }

        // Check if IP is currently locked
        if($ipAttempt && $ipAttempt->locked_until && $ipAttempt->locked_until->isFuture()){
            $minutesRemaining = now()->diffInMinutes($ipAttempt->locked_until);
            throw ValidationException::withMessages([
                'email' => "This IP address has been blocked due to multiple failed login attempts. Please try again after {$minutesRemaining} minute(s).",
            ]);
        }

        $user = User::where('email', $request->email)->whereIn('user_type',['admin','staff'])->first();
        if(!$user){
            // Track failed attempt by IP
            $ipAttempt = $this->recordFailedIpAttempt($ipAddress);

            // Lock IP address after 3 failed attempts
            if($ipAttempt->attempts >= 3){
                $ipAttempt->update([
                    'locked_until' => now()->addHour(),
                    'attempts' => 0, // Reset counter after locking
                ]);

                throw ValidationException::withMessages([
                    'email' => 'This IP address has been blocked for 1 hour due to multiple failed login attempts. Please try again later.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => 'Invalid credentials',
            ]);
        }

        // Clear lockout if it has expired
        if($user->locked_until && $user->locked_until->isPast()){
            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);
        }

        // Check if user is currently locked out
        if($user->locked_until && $user->locked_until->isFuture()){
            $minutesRemaining = now()->diffInMinutes($user->locked_until);
            throw ValidationException::withMessages([
                'email' => "Your account has been locked due to multiple failed login attempts. Please try again after {$minutesRemaining} minute(s).",
            ]);
        }

        // Attempt login
        if(!Auth::guard('web')->attempt($request->only('email', 'password'))){
            // Track failed attempt by IP
            $ipAttempt = $this->recordFailedIpAttempt($ipAddress);

            // Increment failed login attempts for user
            $user->increment('failed_login_attempts');

            // Lock IP address after 3 failed attempts
            if($ipAttempt->attempts >= 3){
                $ipAttempt->update([
                    'locked_until' => now()->addHour(),
                    'attempts' => 0, // Reset counter after locking
                ]);

                throw ValidationException::withMessages([
                    'email' => 'This IP address has been blocked for 1 hour due to multiple failed login attempts. Please try again later.',
                ]);
            }

            // Lock user account after 3 failed attempts
            if($user->failed_login_attempts >= 3){
                $user->update([
                    'locked_until' => now()->addHour(),
                    'failed_login_attempts' => 0, // Reset counter after locking
                ]);

                throw ValidationException::withMessages([
                    'email' => 'Your account has been locked for 1 hour due to multiple failed login attempts. Please try again later.',
                ]);
            }

            $remainingAttempts = 3 - $user->failed_login_attempts;
            throw ValidationException::withMessages([
                'email' => "Invalid credentials. {$remainingAttempts} attempt(s) remaining.",
            ]);
        }

        // Reset failed login attempts on successful login (both IP and user)
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Reset IP attempts on successful login
        if($ipAttempt){
            $ipAttempt->update([
                'attempts' => 0,
                'locked_until' => null,
            ]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Record a failed login attempt by IP address
     */
    private function recordFailedIpAttempt(string $ipAddress): FailedLoginAttempt
    {
        $ipAttempt = FailedLoginAttempt::where('ip_address', $ipAddress)->first();

        if($ipAttempt){
            $ipAttempt->increment('attempts');
            $ipAttempt->refresh();
        } else {
            $ipAttempt = FailedLoginAttempt::create([
                'ip_address' => $ipAddress,
                'attempts' => 1,
            ]);
        }

        return $ipAttempt;
    }

    public function logout(Request $request){
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
