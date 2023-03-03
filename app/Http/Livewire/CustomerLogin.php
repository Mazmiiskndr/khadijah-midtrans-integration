<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CustomerLogin extends Component
{
    public $password;
    public $email;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    protected $messages = [
        'email.required' => 'Email harus diisi!',
        'email.email' => 'Format harus email!',
        'password.required' => 'Password harus diisi!',
    ];

    public function updated($property)
    {
        // Every time a property changes
        // (only `text` for now), validate it
        $this->validateOnly($property);
    }
    public function submit()
    {

        $this->validate();
        if (Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password])) {
            return redirect('');
        } else {
            // login gagal
            session()->flash('error', 'Alamat Email atau Password Anda salah!.');
        }
    }

    public function render()
    {
        return view('livewire.customer-login');
    }
}
