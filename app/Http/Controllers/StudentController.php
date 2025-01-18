<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /* Read */
    public function index(Student $student)
    {
        return Inertia::render('Student/StudentsDashboard', [
            'studentsData' => $student->all(),
            'count' => $student->count(),
        ]);
    }

    /* Create */
    public function store(Request $request, Student $student)
    {
        $student->create($request->validate([
            'first_name' => 'required|max:255|min:2',
            'last_name' => 'required|max:255|min:2',
            'department' => 'required|max:255|min:2',
            'email' => 'required|email|max:255|unique:students,email',
        ]));

        return back()->with('message', 'Student added successfully');
    }

    /* Update */
    public function update(Request $request, Student $student, $student_id)
    {
        // Menambahkan log untuk siapa yang mengupdate dan data yang diterima
        $user = Auth::user(); // Ambil pengguna yang sedang login
        Log::info('Update request received for student ID: ' . $student_id);

        // Gabungkan data request dengan informasi pengguna yang mengupdate
        $requestData = $request->all();
        $requestData['updated_by'] = $user->name . ' (ID: ' . $user->id . ')'; // Menambahkan siapa yang mengupdate

        // Log data request termasuk siapa yang mengupdate
        Log::info('Update request data: ', $requestData);

        // Validasi data request
        $validatedData = $request->validate(
            [
                'first_name' => 'required|max:255|min:2',
                'last_name' => 'required|max:255|min:2',
                'department' => 'required|max:255|min:2',
                'email' => 'required|email|max:255',
            ],
            [
                'email.unique' => 'The email has already been taken.', // Custom error message for unique rule
            ]
        );

        // Cari mahasiswa berdasarkan ID
        $student = $student->findOrFail($student_id);

        // Perbarui data mahasiswa
        $student->update($validatedData);

        // Kembalikan respon
        return back()->with('message', 'Student updated successfully');
    }


    /* Delete */
    public function destroy(Student $student, $student_id)
    {
        $student = $student->findOrFail($student_id);

        $student->delete();

        // You can also use redirect()->route('your_directory')
        return back()->with('message', 'Student deleted successfully');
    }
}
