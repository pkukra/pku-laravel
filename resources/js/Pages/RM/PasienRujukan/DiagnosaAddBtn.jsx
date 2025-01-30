import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import { useForm } from "@inertiajs/react";
import { router } from "@inertiajs/react";

export default function DiagnosaAddBtn({ className = "", disabled }) {
    const { data, setData, reset, errors, processing } = useForm({
        first_name: "",
        last_name: "",
        department: "",
        email: "",
    });

    const submit = (e) => {
        e.preventDefault();

        router.post("/addDiagnosa", data, {
            onSuccess: () => {
                // Reset form
                reset();
                // Close the modal
                document.getElementById("modal_add_diadnosa").close();
            },
        });
    };

    return (
        <>
            <button
                onClick={() =>
                    document.getElementById("modal_add_diadnosa").showModal()
                }
                className="btn btn-xs btn-primary"
                disabled={disabled}
            >
                Add Diagnosa
            </button>

            <dialog id="modal_add_diadnosa" className="modal">
                <div className="modal-box w-11/12 max-w-5xl">
                    <form method="dialog">
                        <button className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                            ✕
                        </button>
                    </form>
                    <h3 className="font-bold text-lg">Hello!</h3>
                    <p className="py-4">
                        Press ESC key or click outside to close
                    </p>
                </div>
                <form method="dialog" className="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>

        </>
    );
}
