import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import axios from "axios";
import { Col, Row } from "antd";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PasienRujukanDetailProfile from "./PasienRujukanDetailProfile";
import PasienRujukanDetailDiagnosaList from "./PasienRujukanDetailDiagnosaList";
import PasienRujukanDetailProcedureList from "./PasienRujukanDetailProcedureList";

export default function PasienRujukanDetail({ auth, pasien }) {
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(true); // Loading state
    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]); // untuk disable diagnosa terpiluh, agar saat menampilkan list diagnosa tidak terpilih 2 kali
    const [isModalHapusDiagnosaOpen, setIsModalHapusDiagnosaOpen] =
        useState(false); // Modal visibility
    const [currentDiagnosa, setCurrentDiagnosa] = useState(null); // Track current diagnosa for deletion

    const [procedure, setProcedure] = useState([]); // State untuk menyimpan data procedure
    const [loadingFetchProcedure, setLoadingFetchProcedure] = useState(true); // Loading state
    const [deleteProcedureId, setDeleteProcedureId] = useState(null); // Track which procedure is being deleted
    const [selectedProcedure, setSelectedProcedure] = useState([]); // untuk disable procedure terpiluh, agar saat menampilkan list procedure tidak terpilih 2 kali
    const [isModalHapusProcedureOpen, setIsModalHapusProcedureOpen] =
        useState(false); // Modal visibility
    const [currentProcedure, setCurrentProcedure] = useState(null); // Track current procedure for deletion

    // Memanggil endpoint untuk mendapatkan data diagnosa
    useEffect(() => {
        fetchDiagnosa();
        fetchProcedure();
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

    // Fungsi untuk mengambil data diagnosa
    const fetchProcedure = () => {
        axios
            .get(
                route("rm.pasien-rujukan.list_procedure", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setSelectedProcedure(
                    response.data.data.map((item) => item.MRPKD_PENYAKIT)
                );
                setProcedure(response.data.data); // Simpan data yang diterima ke dalam state
                setLoadingFetchProcedure(false); // Set loading ke false setelah data diterima
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
                setLoadingFetchProcedure(false); // Set loading ke false jika ada error
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

    // Fungsi untuk menhapus procedure setiap detail pasien by id
    const deleteProcedure = (id, kode) => {
        setDeleteProcedureId(id); // Set loading for the specific procedure being deleted
        axios
            .delete(
                route("rm.pasien-rujukan.delete_procedure", {
                    id: id,
                })
            )
            .then((response) => {
                // Menghapus kode procedure dari selectedProcedure setelah berhasil dihapus
                setSelectedProcedure((prevSelectedProcedure) =>
                    prevSelectedProcedure.filter((item) => item !== kode)
                );
                fetchProcedure(); // Memanggil ulang untuk mendapatkan data procedure terbaru
                setIsModalHapusProcedureOpen(false); // Close the modal after deletion
                setDeleteProcedureId(null); // Reset deleteProcedureId after deletion
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
                setDeleteProcedureId(null); // Reset deleteProcedureId if error occurs
                setIsModalHapusProcedureOpen(false); // Close the modal if an error occurs
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

    // Function to handle cancel (closing the modal)
    const handleCancelDelProcedure = () => {
        setIsModalHapusProcedureOpen(false); // Close the modal
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

            <Row>
                <Col span={12}>
                    <PasienRujukanDetailDiagnosaList
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
                </Col>
                <Col span={12}>
                    {/* <PasienRujukanDetailProcedureList
                        pasien={pasien}
                        procedure={procedure}
                        loadingFetchProcedure={loadingFetchProcedure}
                        deleteProcedureId={deleteProcedureId}
                        showDeleteConfirm={showDeleteConfirm}
                        selectedProcedure={selectedProcedure}
                        setSelectedProcedure={setSelectedProcedure}
                        fetchProcedure={fetchProcedure}
                        isModalHapusProcedureOpen={isModalHapusProcedureOpen}
                        currentProcedure={currentProcedure}
                        deleteProcedure={deleteProcedure}
                        handleCancelDelProcedure={handleCancelDelProcedure}
                    /> */}
                </Col>
            </Row>
        </AuthenticatedLayout>
    );
}
