import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import { useForm } from "@inertiajs/react";
import { router } from "@inertiajs/react";

export default function AddStudentButton({ className = "", disabled }) {
    const { data, setData, reset, errors, processing } = useForm({
        first_name: "",
        last_name: "",
        department: "",
        email: "",
    });

    const submit = (e) => {
        e.preventDefault();

        router.post("/addStudent", data, {
            onSuccess: () => {
                // Reset form
                reset();
                // Close the modal
                document.getElementById("my_modal_2").close();
            },
        });
    };

    return (
        <>
            <button
                onClick={() =>
                    document.getElementById("my_modal_2").showModal()
                }
                className={
                    `inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 focus:bg-green-800 active:bg-green-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest  focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 ${
                        disabled && "opacity-25"
                    } ` + className
                }
                disabled={disabled}
            >
                Add Student
            </button>

            <dialog id="my_modal_2" className="modal">
                <div className="modal-box bg-slate-50">
                    <div className="modal-header">
                        <form method="dialog">
                            <button
                                className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                                onClick={() => reset()}
                            >
                                ✕
                            </button>
                        </form>
                    </div>
                    <div className="modal-body">
                        <h3 className="font-bold text-lg">Add New Student</h3>
                        <form onSubmit={submit} className="mt-6 space-y-6">
                            {/* First Name */}
                            <div>
                                <InputLabel
                                    htmlFor="first_name"
                                    value="First Name"
                                />

                                <TextInput
                                    id="first_name"
                                    className="mt-1 block w-full"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData("first_name", e.target.value)
                                    }
                                    required
                                    isFocused
                                    autoComplete="first_name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.first_name}
                                />
                            </div>

                            {/* Last Name */}
                            <div>
                                <InputLabel
                                    htmlFor="last_name"
                                    value="Last Name"
                                />

                                <TextInput
                                    id="last_name"
                                    className="mt-1 block w-full"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData("last_name", e.target.value)
                                    }
                                    required
                                    isFocused
                                    autoComplete="last_name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.last_name}
                                />
                            </div>

                            {/* Department */}
                            <div>
                                <InputLabel
                                    htmlFor="department"
                                    value="Department"
                                />

                                <TextInput
                                    id="department"
                                    className="mt-1 block w-full"
                                    value={data.department}
                                    onChange={(e) =>
                                        setData("department", e.target.value)
                                    }
                                    required
                                    isFocused
                                    autoComplete="department"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.department}
                                />
                            </div>

                            {/* Email */}
                            <div>
                                <InputLabel htmlFor="email" value="Email" />

                                <TextInput
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                    required
                                    autoComplete="username"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <button
                                className={`w-full text-center items-center px-4 py-2 bg-green-600 hover:bg-green-700 focus:bg-green-800 active:bg-green-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest  focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 ${
                                    disabled && "opacity-25"
                                } `}
                                disabled={processing}
                            >
                                Add New Student
                            </button>
                        </form>
                    </div>
                    <div className="modal-action">
                        <form method="dialog">
                            <button
                                className="btn text-black border-0 bg-gray-300 hover:bg-gray-400"
                                onClick={() => reset()}
                            >
                                Close
                            </button>
                        </form>
                    </div>
                </div>
                <form method="dialog" className="modal-backdrop">
                    <button onClick={() => reset()}></button>
                </form>
            </dialog>
        </>
    );
}
