import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import axios from "axios"; // Import axios untuk mengambil data
import { Modal } from "antd"; // Import Modal
import Button from "@/Components/Button";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukansDetailProfile from "./PasienRujukansDetailProfile";
import DiagnosaAddBtn from "./DiagnosaAddBtn";

export default function PasienRujukansDetail({ auth, pasien }) {
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(true); // Loading state
    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]);
    const [isModalHapusDiagnosaOpen, setIsModalHapusDianosaOpen] = useState(false); // Modal visibility
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
                setDeleteDiagnosaId(null); // Reset deleteDiagnosaId after deletion
                fetchDiagnosa(); // Memanggil ulang untuk mendapatkan data diagnosa terbaru
                setIsModalHapusDianosaOpen(false); // Close the modal after deletion
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
                setDeleteDiagnosaId(null); // Reset deleteDiagnosaId if error occurs
                setIsModalHapusDianosaOpen(false); // Close the modal if an error occurs
            });
    };

    // Function to show the modal with the diagnosa info for deletion
    const showDeleteConfirm = (item) => {
        setCurrentDiagnosa(item); // Set the current diagnosa to be deleted
        setIsModalHapusDianosaOpen(true); // Show the modal
    };

    // Function to handle cancel (closing the modal)
    const handleCancel = () => {
        setIsModalHapusDianosaOpen(false); // Close the modal
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
            <PasienRujukansDetailProfile pasien={pasien} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div className="diagnosa-list">
                    <div className="card bg-base-100">
                        <div className="card-body">
                            {loadingFetchDiagnosa ? (
                                <>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                </>
                            ) : (
                                <>
                                    <div className="grid grid-cols-5 gap-5">
                                        <div className="col-span-4">
                                            <strong>Diagnosa</strong>
                                        </div>
                                        <div className="col-span-1">
                                            <DiagnosaAddBtn
                                                className="float-end"
                                                pasien={pasien}
                                                refreshDiagnosa={fetchDiagnosa}
                                                selectedDiagnosa={
                                                    selectedDiagnosa
                                                }
                                                setSelectedDiagnosa={
                                                    setSelectedDiagnosa
                                                }
                                            />
                                        </div>
                                    </div>
                                    <table
                                        className="table table-xs"
                                        style={{ width: "100%" }}
                                    >
                                        <thead>
                                            <tr>
                                                <th style={{ width: "5%" }}>
                                                    NO
                                                </th>
                                                <th style={{ width: "10%" }}>
                                                    Kode
                                                </th>
                                                <th style={{ width: "72%" }}>
                                                    Penyakit
                                                </th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {diagnosa.map((item, index) => (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        {item.MRPKD_PENYAKIT}
                                                    </td>
                                                    <td>{item.PENYAKIT}</td>
                                                    <td>
                                                        <Button
                                                            loading={
                                                                deleteDiagnosaId ===
                                                                item.ID
                                                            }
                                                            className="btn btn-xs btn-outline btn-error"
                                                            onClick={() =>
                                                                showDeleteConfirm(
                                                                    item
                                                                )
                                                            }
                                                        >
                                                            hapus
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Diagnosa"
                open={isModalHapusDiagnosaOpen}
                onOk={() =>
                    currentDiagnosa &&
                    deleteDiagnosa(
                        currentDiagnosa.ID,
                        currentDiagnosa.MRPKD_PENYAKIT
                    )
                }
                onCancel={handleCancel}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }} // Make "Ya" button a danger button
            >
                <p>Apakah anda yakin ingin menghapus diagnosa ini?</p>
            </Modal>
        </AuthenticatedLayout>
    );
}
