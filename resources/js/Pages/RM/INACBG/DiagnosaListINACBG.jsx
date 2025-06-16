import React, { useState, useEffect, useRef } from "react";
import {
    Modal,
    Spin,
    Card,
    Select,
    AutoComplete,
    Row,
    Col,
    notification,
    Table,
    Button,
    Tooltip,
} from "antd";
import { PlusOutlined, LoadingOutlined } from "@ant-design/icons";
import axios from "axios";

export default function Index({
    pasien,
    trigerFetchDiagnosa,
    setDiagnosaHasErr,
    fetchINACBGData,
    isFinalINACBG,
}) {
    const columns = [
        {
            title: "Status",
            dataIndex: "MRPSTAT_DIAG",
            key: "ID",
            width: 30,
        },
        {
            title: "Kode",
            dataIndex: "MRPKD_PENYAKIT",
            key: "MRPKD_PENYAKIT",
            width: 30,
        },
        {
            title: "Penyakit",
            dataIndex: "PENYAKIT",
            key: "PENYAKIT",
            render: (text, record) => (
                <>
                    {text}
                    {record.IS_ERROR == 1 && (
                        <strong style={{ color: "red" }}>
                            {" "}
                            ({record.ERROR_MESSAGE})
                        </strong>
                    )}
                </>
            ),
        },
        {
            title: "Action",
            key: "action",
            align: "center",
            render: (_, record) => (
                <>
                    <Button
                        disabled={
                            isFinalINACBG ||
                            (loadingDeleteDiagnosa &&
                                record.ID === deleteDiagnosaId)
                        }
                        size="small"
                        block
                        variant="outlined"
                        onClick={() => {
                            setDataDiagnosaToEdit(record);
                            setEditStatusDiagForm(record.MRPSTAT_DIAG);
                            setEditKasusForm(record.MRPKD_KASUS);
                            setEditDiagnosaForm(record.MRPKD_PENYAKIT);
                            setEditDiagnosaDisplay(
                                `${record.MRPKD_PENYAKIT} - ${record.PENYAKIT}`
                            );
                        }}
                    >
                        Edit
                    </Button>{" "}
                    <br />
                    <Button
                        style={{ marginTop: 5 }}
                        disabled={
                            isFinalINACBG ||
                            (loadingDeleteDiagnosa &&
                                record.ID === deleteDiagnosaId)
                        }
                        block
                        size="small"
                        variant="outlined"
                        color="danger"
                        onClick={() => showDeleteConfirm(record)}
                    >
                        Hapus
                    </Button>
                </>
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

    //edit diagnosa
    const [dataDiagnosaToEdit, setDataDiagnosaToEdit] = useState(null);
    const [editStatusDiagForm, setEditStatusDiagForm] = useState(null);
    const [editKasusForm, setEditKasusForm] = useState(null);
    const [editDiagnosaForm, setEditDiagnosaForm] = useState(null);
    const [editDiagnosaDisplay, setEditDiagnosaDisplay] = useState("");

    const no_sep = pasien?.FMNOSEP || null;
    let pasien_id = pasien?.FRPPASIEN_ID;
    let kode_reg = pasien?.FRPNOTRANSAKSIKJ;
    let customer_id = pasien?.FRPCUSTOMER_ID;
    let pasien_tgl_transaksi = pasien?.FRPTGL;
    if (pasien?.JENIS_RAWAT == "ranap") {
        customer_id = pasien?.PRWIKD_CUSTOMER;
        kode_reg = pasien?.FTNO_TRANSAKSI;
        pasien_id = pasien?.FTKD_PASIEN;
        pasien_tgl_transaksi = pasien?.FTTGL_TRANSAKSI;
    }

    // Fungsi untuk mengambil data diagnosa
    const fetchDiagnosa = () => {
        setLoadingFetchDiagnosa(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_diagnosa", {
                    kode_reg: kode_reg,
                    no_sep: no_sep,
                })
            )
            .then(({ data }) => {
                const diagnosaData = data?.data || [];

                setSelectedDiagnosa(
                    diagnosaData.map((item) => item.MRPKD_PENYAKIT)
                );
                setDiagnosa(diagnosaData);
                // Cek apakah ada data error
                const hasError = diagnosaData.some(
                    (item) => item.IS_ERROR == "1"
                );

                setDiagnosaHasErr(hasError); // Set state ke true jika ada error
                if (hasError) {
                    console.warn("Ditemukan diagnosa dengan error.");
                }
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
                setDiagnosaHasErr(true); // Set error juga jika request gagal
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
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_diagnosa"),
                {
                    icd10_code: selectedDiagnosaForm,
                    no_transaksikj: kode_reg,
                    no_sep: no_sep,
                    no_rm: pasien_id,
                    kd_unit: "",
                    tgl_masuk: pasien_tgl_transaksi,
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
            fetchINACBGData();

            inputRefStatusDdiagnosa.current?.focus();
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
                fetchINACBGData();
                setLoadingDeleteDiagnosa(false);
                setIsModalHapusDiagnosaOpen(false);
            });
    };

    const saveEditedDiagnosa = async () => {
        if (!dataDiagnosaToEdit) return;

        try {
            const response = await axios.put(
                route("rm.pasien-rujukan.update_diagnosa", {
                    id: dataDiagnosaToEdit.ID,
                }),
                {
                    icd10_code: editDiagnosaForm,
                    status_diagnosa: editStatusDiagForm,
                    kasus: editKasusForm,
                }
            );

            if (response?.data?.status === "ok") {
                notification.success({
                    placement: "bottomRight",
                    message: "Berhasil",
                    description: "Diagnosa berhasil diupdate",
                });
            } else {
                notification.error({
                    placement: "bottomRight",
                    message: "Gagal",
                    description: "Diagnosa gagal diupdate",
                });
            }

            setDataDiagnosaToEdit(null);
            fetchDiagnosa();
            fetchINACBGData();
        } catch (error) {
            console.error("Error updating diagnosa:", error);
            notification.error({
                message: "Error",
                description: "Terjadi kesalahan saat update diagnosa.",
            });
        }
    };

    const inputRefStatusDdiagnosa = useRef(null);

    useEffect(() => {
        fetchDiagnosa();
        const handleKeyDown = (event) => {
            // Cek apakah Shift dan F1 ditekan bersamaan
            if (event.shiftKey && event.key === "F1") {
                event.preventDefault(); // Mencegah aksi default browser
                inputRefStatusDdiagnosa.current?.focus();
            }
        };

        window.addEventListener("keydown", handleKeyDown);

        return () => {
            window.removeEventListener("keydown", handleKeyDown);
        };
    }, [trigerFetchDiagnosa]);

    return (
        <Card title={`Diagnosa`}>
            <Row gutter={16} style={{ marginBottom: 10 }}>
                <Col span={5}>
                    <Tooltip
                        title="Shift+F1 untuk shortcut"
                        placement="topLeft"
                    >
                        <Select
                            disabled={isFinalINACBG}
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
                                { value: "4", label: "4-Penyebeb Kematian" },
                            ]}
                            onChange={(value) => {
                                setSelectedStatusDiagForm(value);
                            }}
                            value={selectedStatusDiagForm}
                        />
                    </Tooltip>
                </Col>
                <Col span={4}>
                    <Select
                        disabled={isFinalINACBG}
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
                            disabled:
                                isFinalINACBG ||
                                selectedDiagnosa.includes(item.KD_PENYAKIT), // Disable if already selected
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
                            selectedDiagnosaForm === null ||
                            isFinalINACBG
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

            {/* Modal for Edit Diagnosa*/}
            <Modal
                width={1000}
                title="Edit Diagnosa"
                open={!!dataDiagnosaToEdit}
                onOk={saveEditedDiagnosa}
                onCancel={() => setDataDiagnosaToEdit(null)}
                okText="Simpan"
                cancelText="Batal"
            >
                <Row gutter={16}>
                    <Col span={5}>
                        <Select
                            style={{ width: "100%" }}
                            placeholder="STATUS DIAGNOSA"
                            value={editStatusDiagForm}
                            onChange={setEditStatusDiagForm}
                            options={[
                                { value: "5", label: "5-Diagnosa Akhir" },
                                { value: "1", label: "1-Diagnosa Lain" },
                                { value: "2", label: "2-Komplikasi" },
                                { value: "0", label: "0-Diagnosa Awal" },
                                { value: "3", label: "3-Penyebab Luar" },
                                { value: "4", label: "4-Penyebab Kematian" },
                            ]}
                        />
                    </Col>
                    <Col span={4}>
                        <Select
                            style={{ width: "100%" }}
                            placeholder="Lama Baru"
                            value={editKasusForm}
                            onChange={setEditKasusForm}
                            options={[
                                { value: "0", label: "0 Baru" },
                                { value: "1", label: "1 Lama" },
                            ]}
                        />
                    </Col>
                    <Col span={15}>
                        <AutoComplete
                            style={{ width: "100%" }}
                            options={anotherOptions.map((item) => ({
                                value: `${item.KD_PENYAKIT} - ${item.PENYAKIT}`,
                                label: (
                                    <div>
                                        <strong>{item.KD_PENYAKIT}</strong> -{" "}
                                        {item.PENYAKIT}
                                    </div>
                                ),
                                disabled:
                                    isFinalINACBG ||
                                    selectedDiagnosa.includes(item.KD_PENYAKIT), // Disable if already selected
                            }))}
                            value={editDiagnosaDisplay}
                            onChange={(text) => {
                                setEditDiagnosaForm(null);
                                setEditDiagnosaDisplay(text);
                            }}
                            onSearch={(text) => fetchSugetDiagnosa(text, 1)}
                            onSelect={(value) => {
                                const code = value.split(" - ")[0];
                                setEditDiagnosaForm(code);
                                setEditDiagnosaDisplay(value);
                            }}
                            placeholder="Cari Diagnosa"
                        />
                    </Col>
                </Row>
                <br />
                <p>{JSON.stringify(dataDiagnosaToEdit)}</p>
            </Modal>
        </Card>
    );
}
