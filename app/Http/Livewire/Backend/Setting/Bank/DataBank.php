<?php

namespace App\Http\Livewire\Backend\Setting\Bank;

use App\Models\Bank;
use Livewire\Component;

class DataBank extends Component
{
    // Public properties
    public $bank_id;
    public $bank_uid;
    public $provider;
    public $rekening_name;
    public $rekening_number;

    // Event listeners
    protected $listeners = [
        'updateBank' => 'handleUpdateBank',
    ];

    // Validation rules for updating a bank
    protected $rules = [
        'provider'        => 'required',
        'rekening_name'   => 'required',
        'rekening_number' => 'required|numeric',
    ];

    // Custom validation error messages
    protected $messages = [
        'provider.required'         => 'Provider tidak boleh kosong',
        'rekening_name.required'    => 'Nama Rekening tidak boleh kosong',
        'rekening_number.required'  => 'Nomor Rekening tidak boleh kosong',
        'rekening_number.numeric'   => 'Nomor Rekening harus angka',
    ];

    /**
     * Mount the component.
     * Fetch the first bank and show its properties.
     */
    public function mount()
    {
        $bank = Bank::first();
        if ($bank) {
            $this->show($bank);
        } else {
            // Here you can also initialize your properties to null if you want
            $this->bank_id = null;
            $this->bank_uid = null;
            $this->provider = null;
            $this->rekening_name = null;
            $this->rekening_number = null;
        }
    }

    /**
     * Handle updated property.
     * @param string $property The property that was updated.
     */
    public function updated($property)
    {
        // Validate only the updated property
        $this->validateOnly($property);
    }

    /**
     * Render the component `data-bank`.
     * @return \Illuminate\Contracts\View\View The view of the component.
     */
    public function render()
    {
        return view('livewire.backend.setting.bank.data-bank');
    }

    /**
     * Show the specified bank.
     * @param Bank $bank The bank to show.
     */
    public function show($bank)
    {
        // Assign the properties of bank to the public variables
        foreach ($bank->getAttributes() as $attribute => $value) {
            if (property_exists($this, $attribute)) {
                $this->{$attribute} = $value;
            }
        }
    }

    /**
     * Update the specified bank.
     * Validate the request and update or create the bank, then flash a success message.
     */
    public function update()
    {
        try {
            // Validate the input
            $this->validate();

            // Find the bank and update it or create a new one
            $bank = Bank::updateOrCreate(
                ['bank_uid' => $this->bank_uid],
                [
                    'provider'        => strtoupper($this->provider),
                    'rekening_name'   => $this->rekening_name,
                    'rekening_number' => $this->rekening_number,
                ]
            );

            // Check if it was an update or a creation
            if ($bank->wasRecentlyCreated) {
                // Flash a success message
                session()->flash('success', 'Data Bank Berhasil di Buat!');
            } else {
                // Flash a success message
                session()->flash('success', 'Data Bank Berhasil di Update!');
            }

            $this->emit('updateOrCreateBank');
        } catch (\Exception $e) {
            // Flash an error message
            session()->flash('error', 'Terjadi Kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Handle 'updateBank' event.
     * Remount the component to refresh the data.
     */
    public function handleUpdateBank()
    {
        $this->mount();
    }
}
