import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import axios from "axios";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import DiagnosaList from "./PasienRujukanDetailDiagnosaList";

export default function PasienRujukanDetail({ auth, pasien }) {
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(true); // Loading state
    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]); // untuk disable diagnosa terpiluh, agar saat menampilkan list diagnosa tidak terpilih 2 kali
    const [isModalHapusDiagnosaOpen, setIsModalHapusDiagnosaOpen] =
        useState(false); // Modal visibility
    const [currentDiagnosa, setCurrentDiagnosa] = useState(null); // Track current diagnosa for deletion

    // Memanggil endpoint untuk mendapatkan data diagnosa
    useEffect(() => {
        fetchDiagnosa(); // Panggil fungsi fetchDiagnosa saat komponen di-mount
    }, []); // Efek hanya dijalankan sekali setelah komponen di-mount

    // Fungsi untuk mengambil data diagnosa
    const fetchDiagnosa = () => {
        axios
            .get(
                route("rm.pasien-rujukan.list_diagnosa", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setSelectedDiagnosa(
                    response.data.data.map((item) => item.MRPKD_PENYAKIT)
                );
                setDiagnosa(response.data.data); // Simpan data yang diterima ke dalam state
                setLoadingFetchDiagnosa(false); // Set loading ke false setelah data diterima
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
                setLoadingFetchDiagnosa(false); // Set loading ke false jika ada error
            });
    };

    // Fungsi untuk menhapus diagnosa setia detail pasien by id
    const deleteDiagnosa = (id, kode) => {
        setDeleteDiagnosaId(id); // Set loading for the specific diagnosa being deleted
        axios
            .delete(
                route("rm.pasien-rujukan.delete_diagnosa", {
                    id: id,
                })
            )
            .then((response) => {
                // Menghapus kode diagnosa dari selectedDiagnosa setelah berhasil dihapus
                setSelectedDiagnosa((prevSelectedDiagnosa) =>
                    prevSelectedDiagnosa.filter((item) => item !== kode)
                );
                fetchDiagnosa(); // Memanggil ulang untuk mendapatkan data diagnosa terbaru
                setIsModalHapusDiagnosaOpen(false); // Close the modal after deletion
                setDeleteDiagnosaId(null); // Reset deleteDiagnosaId after deletion
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
                setDeleteDiagnosaId(null); // Reset deleteDiagnosaId if error occurs
                setIsModalHapusDiagnosaOpen(false); // Close the modal if an error occurs
            });
    };

    // Function to show the modal with the diagnosa info for deletion
    const showDeleteConfirm = (item) => {
        setCurrentDiagnosa(item); // Set the current diagnosa to be deleted
        setIsModalHapusDiagnosaOpen(true); // Show the modal
    };

    // Function to handle cancel (closing the modal)
    const handleCancelDelDiagnosa = () => {
        setIsModalHapusDiagnosaOpen(false); // Close the modal
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    Detail Kunjungan Pasien
                </p>
            }
        >
            <Head title="Pasien Rujukan List" />
            <PasienRujukanDetailProfile pasien={pasien} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <DiagnosaList
                    pasien={pasien}
                    diagnosa={diagnosa}
                    loadingFetchDiagnosa={loadingFetchDiagnosa}
                    deleteDiagnosaId={deleteDiagnosaId}
                    showDeleteConfirm={showDeleteConfirm}
                    selectedDiagnosa={selectedDiagnosa}
                    setSelectedDiagnosa={setSelectedDiagnosa}
                    fetchDiagnosa={fetchDiagnosa}
                    isModalHapusDiagnosaOpen={isModalHapusDiagnosaOpen}
                    currentDiagnosa={currentDiagnosa}
                    deleteDiagnosa={deleteDiagnosa}
                    handleCancelDelDiagnosa={handleCancelDelDiagnosa}
                />
            </div>
        </AuthenticatedLayout>
    );
}
