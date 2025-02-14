import React, { useState, useEffect } from "react";
import {
    Modal, Spin,
    Card,
    Select,
    AutoComplete,
    Row,
    Col,
    notification,
    Table,
    Button,
} from "antd";
import { PlusOutlined, LoadingOutlined  } from "@ant-design/icons";
import axios from "axios";

export default function Index({ pasien }) {
    const columns = [
        {
            title: "Status",
            dataIndex: "MRPSTAT_DIAG",
            key: "ID",
        },
        {
            title: "Lama/Baru",
            dataIndex: "MRPKASUS",
            key: "ID",
        },
        {
            title: "Kode",
            dataIndex: "MRPKD_PENYAKIT",
            key: "MRPKD_PENYAKIT",
        },
        {
            title: "Penyakit",
            dataIndex: "PENYAKIT",
            key: "PENYAKIT",
        },
        {
            title: "Action",
            key: "action",
            align:"center",
            render: (_, record) => (
                <Button
                    disabled={
                        loadingDeleteDiagnosa && record.ID === deleteDiagnosaId
                    }
                    size="small"
                    variant="outlined"
                    color="danger"
                    onClick={() => showDeleteConfirm(record)}
                >
                    hapus
                </Button>
            ),
        },
    ];

    const [anotherOptions, setAnotherOptions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [selectedDiagnosaForm, setSelectedDiagnosaForm] = useState(null);
    const [selectedStatusDiagForm, setSelectedStatusDiagForm] = useState(null);
    const [selectedKasusForm, setSelectedKasusForm] = useState(null);
    const [loadingSaveDiag, setLoadingSaveDiag] = useState(false); // Loading state for each diagnosa
    const [selectedDiagnosaDisplay, setSelectedDiagnosaDisplay] = useState(""); // Stores the full value for display
    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [isModalHapusDiagnosaOpen, setIsModalHapusDiagnosaOpen] =
        useState(false); // Modal visibility state
    const [loadingDeleteDiagnosa, setLoadingDeleteDiagnosa] = useState(false); // State loading untuk penghapusan diagnosa
    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]); // untuk disable diagnosa terpiluh, agar saat menampilkan list diagnosa tidak terpilih 2 kali
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(true); // Loading state

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

    // Fetch diagnosa with lazy loading support
    const fetchSugetDiagnosa = async (query, pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.cari_penyakit"),
                {
                    query,
                    page: pageNumber,
                }
            );
            // If no results, mark hasMore as false
            if (response.data.data.length === 0) {
                setHasMore(false);
            }
            // If it's the first page, reset the results, otherwise append new results
            if (pageNumber === 1) {
                setAnotherOptions(response.data.data);
            } else {
                setAnotherOptions((prev) => [...prev, ...response.data.data]);
            }
            setPage(pageNumber); // Update the current page
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoading(false);
        }
    };

    // Lazy load when the user scrolls to the bottom
    const onScroll = (e) => {
        const bottom =
            e.target.scrollHeight ===
            e.target.scrollTop + e.target.clientHeight;
        if (bottom && hasMore && !loading) {
            // If scrolled to bottom and more data is available, load the next page
            fetchSugetDiagnosa(value, page + 1);
        }
    };

    // Function to save diagnosa
    const saveDiagnosa = async () => {
        setLoadingSaveDiag(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_diagnosa"),
                {
                    icd10_code: selectedDiagnosaForm,
                    no_transaksikj: pasien.FRPNOTRANSAKSIKJ,
                    no_rm: pasien.FRPPASIEN_ID,
                    kd_unit: pasien.FRPUNIT,
                    tgl_masuk: pasien.FRPTGL,
                    status_diagnosa: selectedStatusDiagForm,
                    kasus: selectedKasusForm,
                }
            );

            if (response?.data?.status === "ok") {
                return notification.success({
                    placement: "bottomRight",
                    message: "Sukses!",
                    description: "Diagnosa berhasil ditambahkan.",
                });
            }
            return notification.error({
                placement: "bottomRight",
                message: "Terjadi Kesalahan!",
                description: "Diagnosa gagal ditambahkan.",
            });
        } catch (error) {
            console.error("Error saving diagnosa:", error);
        } finally {
            fetchDiagnosa();
            setLoadingSaveDiag(false);
            setSelectedDiagnosaForm(null);
            setSelectedStatusDiagForm(null);
            setSelectedKasusForm(null);
            setSelectedDiagnosaDisplay(null);
        }
        return;
    };

    // Function to show the modal with the diagnosa info for deletion
    const showDeleteConfirm = (item) => {
        setDeleteDiagnosaId(item.ID); // Set the current diagnosa to be deleted
        setIsModalHapusDiagnosaOpen(true); // Show the modal
    };

    // Function to handle cancel (closing the modal)
    const handleCancelDelDiagnosa = () => {
        setIsModalHapusDiagnosaOpen(false); // Close the modal
    };

    // Fungsi untuk menhapus diagnosa setia detail pasien by id
    const deleteDiagnosa = (id, kode) => {
        setLoadingDeleteDiagnosa(true); // Set loading true saat mulai menghapus
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
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingDeleteDiagnosa(false);
                setIsModalHapusDiagnosaOpen(false);
            });
    };

    // Memanggil endpoint untuk mendapatkan data diagnosa
    useEffect(() => {
        fetchDiagnosa();
    }, []); // Efek hanya dijalankan sekali setelah komponen di-mount

    return (
        <Card title={`Diagnosa`} style={{marginBottom:10}}>
            <Row gutter={16} style={{ marginBottom: 10 }}>
                <Col span={5}>
                    <Select
                        showSearch
                        style={{ width: "100%" }}
                        placeholder="STATUS DIAGNOSA"
                        filterOption={(input, option) =>
                            (option?.label ?? "")
                                .toLowerCase()
                                .includes(input.toLowerCase())
                        }
                        options={[
                            { value: "5", label: "5-Diagnosa Akhir" },
                            { value: "1", label: "1-Diagnosa Lain" },
                            { value: "2", label: "2-Komplikasi" },
                            { value: "0", label: "0-Diagnosa Awal" },
                            { value: "3", label: "3-Penyebab Luar" },
                            { value: "4", label: "4-Penyebeb Kematian" },
                        ]}
                        onChange={(value) => {
                            setSelectedStatusDiagForm(value);
                        }}
                        value={selectedStatusDiagForm}
                    />
                </Col>
                <Col span={3}>
                    <Select
                        showSearch
                        style={{ width: "100%" }}
                        placeholder="Lama Baru"
                        filterOption={(input, option) =>
                            (option?.label ?? "")
                                .toLowerCase()
                                .includes(input.toLowerCase())
                        }
                        options={[
                            { value: "0", label: "Baru" },
                            { value: "1", label: "Lama" },
                        ]}
                        onChange={(value) => {
                            setSelectedKasusForm(value);
                        }}
                        value={selectedKasusForm}
                    />
                </Col>
                <Col span={12}>
                    <AutoComplete
                        allowClear
                        onChange={() => {
                            setSelectedDiagnosaForm(null); // Clear the stored code
                            setSelectedDiagnosaDisplay(""); // Clear the display value
                        }}
                        options={anotherOptions.map((item) => ({
                            value: `${item.KD_PENYAKIT} - ${item.PENYAKIT}`, // Display both code and name
                            label: (
                                <div
                                    style={{
                                        wordBreak: "break-word", // Ensure text wraps
                                        whiteSpace: "normal", // Allow wrapping long words
                                        overflowWrap: "break-word", // Break long words if necessary
                                        display: "block", // Ensure block level behavior for wrapping
                                    }}
                                >
                                    <strong>{item.KD_PENYAKIT}</strong> -{" "}
                                    <span>{item.PENYAKIT}</span>
                                </div>
                            ),
                            disabled: selectedDiagnosa.includes(
                                item.KD_PENYAKIT
                            ), // Disable if already selected
                        }))}
                        style={{ width: "100%" }}
                        onSelect={(value) => {
                            const kdPenyakit = value.split(" - ")[0]; // Extract KD_PENYAKIT
                            const displayValue = value; // Full display value with name and code
                            setSelectedDiagnosaForm(kdPenyakit); // Store only the code
                            setSelectedDiagnosaDisplay(displayValue); // Display both the code and name
                        }}
                        onSearch={(text) => {
                            setSelectedDiagnosaDisplay(text); // Update the display value during search
                            fetchSugetDiagnosa(text, 1); // Trigger the fetch for suggestions
                        }}
                        placeholder="Cari Diagnosa/Penyakit"
                        onScroll={onScroll} // Attach scroll event for lazy loading
                        value={selectedDiagnosaDisplay} // Show both code and name in the input
                    />
                </Col>
                <Col span={4}>
                    <Button
                        type="primary"
                        size="medium"
                        style={{ width: "100%" }}
                        onClick={saveDiagnosa}
                        disabled={
                            loadingSaveDiag ||
                            selectedKasusForm === null ||
                            selectedStatusDiagForm === null ||
                            selectedDiagnosaForm === null
                        }
                    >
                        {loadingSaveDiag ? <Spin indicator={<LoadingOutlined spin />} size="small" /> : <PlusOutlined />}
                    </Button>
                </Col>
            </Row>
            <>
                <Table
                    pagination={false}
                    columns={columns}
                    dataSource={diagnosa}
                    size="small"
                    loading={loadingFetchDiagnosa}
                    rowKey="ID"
                />
            </>
            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Diagnosa"
                open={isModalHapusDiagnosaOpen}
                onOk={() => {
                    deleteDiagnosaId &&
                        deleteDiagnosa(deleteDiagnosaId, selectedDiagnosaForm);
                }}
                onCancel={handleCancelDelDiagnosa}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                <p>Apakah anda yakin ingin menghapus diagnosa ini?</p>
            </Modal>
        </Card>
    );
}
