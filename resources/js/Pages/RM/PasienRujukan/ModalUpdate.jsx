import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import { useForm } from "@inertiajs/react";
import { Inertia } from "@inertiajs/inertia";

export default function ModalUpdate({ id, s }) {
    const {
        data: editData,
        setData: setEditData,
        errors,
        processing,
        reset,
    } = useForm({
        pasien_rujukan_id: s.pasien_rujukan_id,
        first_name: s.first_name,
        last_name: s.last_name,
        department: s.department,
        email: s.email,
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        Inertia.post(`/updatePasienRujukan/${s.pasien_rujukan_id}`, {
            _method: "patch",
            ...editData,
        });
    };

    return (
        <>
            <button
                onClick={() =>
                    document
                        .getElementById(`my_modal_3${s.pasien_rujukan_id}`)
                        .showModal()
                }
                className={`inline-flex items-center px-4 py-2 bg-yellow-400 hover:bg-yellow-500 focus:bg-yellow-600 active:bg-yellow-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest  focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 `}
            >
                Edit
            </button>
            <dialog id={id} className="modal">
                <div className="modal-box bg-slate-50">
                    <div className="modal-header">
                        <form method="dialog">
                            {/* if there is a button in form, it will close the modal */}
                            <button
                                className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                                onClick={() => reset()}
                            >
                                ✕
                            </button>
                        </form>
                    </div>
                    <div className="modal-body">
                        <h3 className="font-bold text-lg">
                            Edit {s.first_name} Details
                            <small className="block">
                                id: {editData.pasien_rujukan_id}
                            </small>
                        </h3>
                        <form
                            onSubmit={handleSubmit}
                            className="mt-6 space-y-6"
                        >
                            {/* First Name */}
                            <div>
                                <InputLabel
                                    htmlFor="first_name"
                                    value="First Name"
                                />

                                <TextInput
                                    id="first_name"
                                    className="mt-1 block w-full"
                                    value={editData.first_name}
                                    onChange={(e) =>
                                        setEditData(
                                            "first_name",
                                            e.target.value
                                        )
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
                                    value={editData.last_name}
                                    onChange={(e) =>
                                        setEditData("last_name", e.target.value)
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
                                    value={editData.department}
                                    onChange={(e) =>
                                        setEditData(
                                            "department",
                                            e.target.value
                                        )
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
                                    value={editData.email}
                                    onChange={(e) =>
                                        setEditData("email", e.target.value)
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
                                className={`w-full text-center items-center px-4 py-2 bg-yellow-400 hover:bg-yellow-500 focus:bg-yellow-600 active:bg-yellow-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest  focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150`}
                                disabled={processing}
                            >
                                Confirm Update
                            </button>
                        </form>
                    </div>
                    <div className="modal-action">
                        <form method="dialog">
                            {/* if there is a button in form, it will close the modal */}
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
