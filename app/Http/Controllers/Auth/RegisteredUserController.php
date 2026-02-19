<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    protected $tenantService;

    public function __construct(TenantProvisioningService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        /*
        // 🔥 CREATE TENANT DATABASE FROM CENTRAL SCHEMA
        try {
            $tenant = $this->tenantService->createTenant($user);
            
            // Optional: Log success
            \Illuminate\Support\Facades\Log::info("Tenant created for user {$user->id}: {$tenant->database_name}");
        } catch (\Exception $e) {
            // If tenant creation fails, delete the user and show error
            $user->delete();
            
            return back()->withErrors([
                'email' => 'Failed to create your account. Please try again.',
            ])->withInput();
        }
        */

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}