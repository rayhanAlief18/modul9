<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pageTitle = 'Employee List';
        // ELOQUENT
        $employees = Employee::all();
        return view('employee.index', [
            'pageTitle' => $pageTitle,
            'employees' => $employees
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee';
        // ELOQUENT
        $positions = Position::all();
        return view('employee.create', compact('pageTitle', 'positions'));
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $messages = [
        'required' => ':Attribute harus diisi.',
        'email' => 'Isi :attribute dengan format yang benar',
        'numeric' => 'Isi :attribute dengan angka'
    ];

    $validator = Validator::make($request->all(), [
        'firstName' => 'required',
        'lastName' => 'required',
        'email' => 'required|email',
        'age' => 'required|numeric',
    ], $messages);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Get File
    $file = $request->file('cv');

    if ($file != null) {
        $originalFilename = $file->getClientOriginalName();
        $encryptedFilename = $file->hashName();

        // Store File
        $file->store('public/files');
    }

    // ELOQUENT
    $employee = New Employee;
    $employee->firstname = $request->firstName;
    $employee->lastname = $request->lastName;
    $employee->email = $request->email;
    $employee->age = $request->age;
    $employee->position_id = $request->position;

    if ($file != null) {
        $employee->original_filename = $originalFilename;
        $employee->encrypted_filename = $encryptedFilename;
    }

    $employee->save();

    return redirect()->route('employees.index');
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';
        // ELOQUENT
        $employee = Employee::find($id);
        return view('employee.show', compact('pageTitle', 'employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $employee = Employee::find($id);
    $positions = Position::all();  // Ambil semua posisi dari database
    return view('employee.edit', compact('employee', 'positions'));
    }



    public function update(Request $request, string $id)
    {
        $employee = Employee::find($id);

    // Validasi data
    $request->validate([
        'firstName' => 'required',
        'lastName' => 'required',
        'email' => 'required|email',
        'age' => 'required|integer',
        'position' => 'required',
        'cv' => 'required|mimes:pdf|max:2048',  // Hanya menerima file PDF dengan ukuran maksimal 2MB
    ]);

    // Hapus file CV lama
    if ($employee->cv && Storage::exists($employee->cv)) {
        Storage::delete($employee->cv);
    }

    // Unggah file CV baru
    $cvPath = $request->file('cv')->store('cvs');

    // Update data karyawan
    $employee->firstName = $request->firstName;
    $employee->lastName = $request->lastName;
    $employee->email = $request->email;
    $employee->age = $request->age;
    $employee->position = $request->position;
    $employee->cv = $cvPath;
    $employee->save();

    return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
}


    public function destroy(string $id)
    {
        $employee = Employee::find($id);

    // Hapus file CV
    if ($employee->cv && Storage::exists($employee->cv)) {
        Storage::delete($employee->cv);
    }

    // Hapus data karyawan dari database
    $employee->delete();

    return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }


}
