import React, { useState, useEffect, useRef } from "react";
import {
    Modal,
    Button,
    Table,
    Select,
    AutoComplete,
    Row,
    Col,
    Spin,
    notification,
} from "antd";
import moment from "moment";
import axios from "axios";
import { EditOutlined, PlusOutlined, LoadingOutlined } from "@ant-design/icons";

export default function Index({ pasien, reFecthListData }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(true);
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loading, setLoading] = useState(false);

    const [anotherOptions, setAnotherOptions] = useState([]);
    const [loadingDeleteDiagnosa, setLoadingDeleteDiagnosa] = useState(false); // State loading untuk penghapusan diagnosa
    const [selectedStatusDiagForm, setSelectedStatusDiagForm] = useState(null);
    const [selectedKasusForm, setSelectedKasusForm] = useState(null);
    const [selectedDiagnosaDisplay, setSelectedDiagnosaDisplay] = useState(""); // Stores the full value for display
    const [loadingSaveDiag, setLoadingSaveDiag] = useState(false); // Loading state for each diagnosa
    const [selectedDiagnosaForm, setSelectedDiagnosaForm] = useState(null);
    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]); // untuk disable diagnosa terpiluh, agar saat menampilkan list diagnosa tidak terpilih 2 kali

    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [isModalHapusDiagnosaOpen, setIsModalHapusDiagnosaOpen] =
        useState(false); // Modal visibility state

    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

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
            align: "center",
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

    const handleModalDiagnosaOpen = () => {
        fetchDiagnosaByNoTransakasi(pasien?.FTNO_TRANSAKSI);
        setModalOpen(true);
    };

    // Fungsi untuk mengambil data diagnosa
    const fetchDiagnosaByNoTransakasi = async (kode_reg) => {
        setDiagnosa([]); // Reset the data
        setLoadingFetchDiagnosa(true);
        axios
            .get(
                route("casemix.ranap-monit.list_diagnosa", {
                    kode_reg: pasien?.FTNO_TRANSAKSI,
                })
            )
            .then((response) => {
                setSelectedDiagnosa(
                    response.data.data.map((item) => item.MRPKD_PENYAKIT)
                );
                setDiagnosa(response?.data?.data || []); // Simpan data yang diterima ke dalam state
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchDiagnosa(false);
            });
    };

    // Fetch diagnosa with lazy loading support
    const fetchSugetDiagnosa = async (query = "a", pageNumber) => {
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
        const now = moment().format("YYYY-MM-DD HH:mm:ss.SSS");
        try {
            const response = await axios.post(
                route("casemix.ranap-monit.save_diagnosa"),
                {
                    icd10_code: selectedDiagnosaForm,
                    no_transaksikj: pasien.FTNO_TRANSAKSI,
                    no_rm: pasien.FTKD_PASIEN,
                    kd_unit: pasien.PRWIKD_KAMAR,
                    tgl_masuk: now,
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
            reFecthListData();
            fetchDiagnosaByNoTransakasi();
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
                route("casemix.ranap-monit.delete_diagnosa", {
                    id: id,
                })
            )
            .then((response) => {
                // Menghapus kode diagnosa dari selectedDiagnosa setelah berhasil dihapus
                setSelectedDiagnosa((prevSelectedDiagnosa) =>
                    prevSelectedDiagnosa.filter((item) => item !== kode)
                );
                fetchDiagnosaByNoTransakasi(); // Memanggil ulang untuk mendapatkan data diagnosa terbaru
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingDeleteDiagnosa(false);
                setIsModalHapusDiagnosaOpen(false);
                reFecthListData();
            });
    };

    useEffect(() => {}, []);
    const inputRefStatusDdiagnosa = useRef(null);

    return (
        <>
            <a onClick={handleModalDiagnosaOpen}>
                <EditOutlined />
            </a>

            <Modal
                title="Edit Kode Diagnosa"
                destroyOnClose
                open={modalOpen}
                closable={false}
                width={"50%"}
                footer={[
                    <Button key="back" onClick={() => setModalOpen(false)}>
                        Cancel
                    </Button>,
                ]}
            >
                <p>
                    No RM: <strong>{pasien.FTKD_PASIEN} </strong> No Transakasi:{" "}
                    <strong>{pasien.FTNO_TRANSAKSI} </strong>
                    Nama Pasien: <strong>{pasien.NAMAPASIEN}</strong>
                </p>

                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={5}>
                        <Select
                            autoFocus
                            ref={inputRefStatusDdiagnosa}
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
                                {
                                    value: "4",
                                    label: "4-Penyebeb Kematian",
                                },
                            ]}
                            onChange={(value) => {
                                setSelectedStatusDiagForm(value);
                            }}
                            value={selectedStatusDiagForm}
                        />
                    </Col>
                    <Col span={4}>
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
                                { value: "0", label: "0 Baru" },
                                { value: "1", label: "1 Lama" },
                            ]}
                            onChange={(value) => {
                                setSelectedKasusForm(value);
                            }}
                            value={selectedKasusForm}
                        />
                    </Col>
                    <Col span={11}>
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
                            onClick={(text) => {
                                fetchSugetDiagnosa("a", 1); // Trigger the fetch for suggestions
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
                            {loadingSaveDiag ? (
                                <Spin
                                    indicator={<LoadingOutlined spin />}
                                    size="small"
                                />
                            ) : (
                                <PlusOutlined />
                            )}
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
            </Modal>

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
        </>
    );
}
