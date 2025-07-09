import React, { useState, useEffect, useRef } from "react";
import {
    Spin,
    Card,
    AutoComplete,
    Row,
    Col,
    notification,
    Table,
    Button,
    Tooltip,
    Modal,
} from "antd";
import { PlusOutlined, LoadingOutlined } from "@ant-design/icons";
import axios from "axios";

export default function Index({
    pasien,
    trigerFetchProcedure,
    setProcedureHasErr,
    fetchINACBGData,
    isFinalINACBG,
}) {
    const columns = [
        {
            title: "Kode",
            dataIndex: "MRTKD_TINDAKAN",
            key: "MRTKD_TINDAKAN",
            width: 30,
        },
        {
            title: "Tindakan",
            dataIndex: "FMI9KETERANGAN",
            key: "FMI9KETERANGAN",
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
            width: 30,
            render: (_, record) => (
                <>
                    <Button
                        block
                        disabled={
                            isFinalINACBG ||
                            (loadingDeleteProcedure &&
                                record.ID == deleteProcedureId)
                        }
                        size="small"
                        style={{ marginBottom: 4 }}
                        onClick={() => {
                            setProcedureToEdit(record);
                            setEditProcedureForm(record.MRTKD_TINDAKAN);
                            setEditProcedureDisplay(
                                `${record.MRTKD_TINDAKAN} - ${record.FMI9KETERANGAN}`
                            );
                        }}
                    >
                        Edit
                    </Button>
                    <br />
                    <Button
                        block
                        disabled={
                            isFinalINACBG ||
                            (loadingDeleteProcedure &&
                                record.ID == deleteProcedureId)
                        }
                        size="small"
                        danger
                        onClick={() => showDeleteConfirm(record)}
                    >
                        Hapus
                    </Button>
                </>
            ),
        },
    ];

    const [loading, setLoading] = useState(false);
    const [anotherOptions, setAnotherOptions] = useState([]);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [selectedProcedureForm, setSelectedProcedureForm] = useState(null);
    const [loadingSaveDiag, setLoadingSaveDiag] = useState(false); // Loading state for each procedure
    const [selectedProcedureDisplay, setSelectedProcedureDisplay] =
        useState(""); // Stores the full value for display
    const [deleteProcedureId, setDeleteProcedureId] = useState(null); // Track which procedure is being deleted
    const [isModalHapusProcedureOpen, setIsModalHapusProcedureOpen] =
        useState(false); // Modal visibility state
    const [loadingDeleteProcedure, setLoadingDeleteProcedure] = useState(false); // State loading untuk penghapusan procedure
    const [selectedProcedure, setSelectedProcedure] = useState([]); // untuk disable procedure terpiluh, agar saat menampilkan list procedure tidak terpilih 2 kali
    const [procedure, setProcedure] = useState([]); // State untuk menyimpan data procedure
    const [loadingFetchProcedure, setLoadingFetchProcedure] = useState(true); // Loading state

    const [procedureToEdit, setProcedureToEdit] = useState(null);
    const [editProcedureForm, setEditProcedureForm] = useState(null);
    const [editProcedureDisplay, setEditProcedureDisplay] = useState("");

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

    // Fungsi untuk mengambil data procedure
    const fetchProcedure = () => {
        setLoadingFetchProcedure(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_procedure", {
                    kode_reg: kode_reg,
                    no_sep: no_sep,
                })
            )
            .then(({ data }) => {
                const procedureData = data?.data || [];
                setSelectedProcedure(
                    procedureData.map((item) => item.MRTKD_TINDAKAN)
                );
                setProcedure(procedureData);

                // Cek apakah ada data error
                const hasError = procedureData.some(
                    (item) => item.IS_ERROR == "1"
                );
                setProcedureHasErr(hasError);
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
            })
            .finally(() => {
                setLoadingFetchProcedure(false);
            });
    };

    // Fetch procedure with lazy loading support
    const fetchSugetProcedure = async (query, pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.search_procedure_cbg"),
                {
                    keyword: query,
                }
            );
            const data = response?.data?.response?.response?.data;
            const results = Array.isArray(data) ? data : [];
            setAnotherOptions(results);
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
            fetchSugetProcedure(value, page + 1);
        }
    };

    // Function to save procedure
    const saveProcedure = async () => {
        setLoadingSaveDiag(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_procedure"),
                {
                    icd9_code: selectedProcedureForm,
                    no_transaksikj: kode_reg,
                    no_sep: no_sep,
                    no_rm: pasien_id,
                    kd_unit: "",
                    tgl_masuk: pasien_tgl_transaksi,
                }
            );

            if (response?.data?.status === "ok") {
                return notification.success({
                    placement: "bottomRight",
                    message: "Sukses!",
                    description: "Procedure berhasil ditambahkan.",
                });
            }
            return notification.error({
                placement: "bottomRight",
                message: "Terjadi Kesalahan!",
                description: "Procedure gagal ditambahkan.",
            });
        } catch (error) {
            console.error("Error saving procedure:", error);
        } finally {
            fetchProcedure();
            fetchINACBGData();
            setLoadingSaveDiag(false);
            setSelectedProcedureForm(null);
            setSelectedProcedureDisplay(null);

            inputRefStatusProcedure.current?.focus();
        }
        return;
    };

    // Function to show the modal with the procedure info for deletion
    const showDeleteConfirm = (item) => {
        setDeleteProcedureId(item.ID); // Set the current procedure to be deleted
        setIsModalHapusProcedureOpen(true); // Show the modal
    };

    // Function to handle cancel (closing the modal)
    const handleCancelDelProcedure = () => {
        setIsModalHapusProcedureOpen(false); // Close the modal
    };

    // Fungsi untuk menhapus procedure setia detail pasien by id
    const deleteProcedure = (id, kode) => {
        setLoadingDeleteProcedure(true); // Set loading true saat mulai menghapus
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
                fetchINACBGData();
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
            })
            .finally(() => {
                setLoadingDeleteProcedure(false);
                setIsModalHapusProcedureOpen(false);
            });
    };

    const handleUpdateProcedure = async () => {
        if (!procedureToEdit || !editProcedureForm) return;

        try {
            const res = await axios.put(
                route("rm.pasien-rujukan.update_procedure", {
                    id: procedureToEdit.ID,
                }),
                {
                    icd9_code: editProcedureForm,
                }
            );

            if (res?.data?.status === "ok") {
                notification.success({
                    placement: "topRight",
                    message: "Berhasil",
                    description: "Procedure berhasil diupdate",
                });
                fetchProcedure();
                fetchINACBGData();
                setProcedureToEdit(null);
                setEditProcedureForm(null);
                setEditProcedureDisplay("");
            } else {
                notification.error({
                    placement: "topRight",
                    message: "Gagal",
                    description: "Procedure gagal diupdate",
                });
            }
        } catch (error) {
            console.error("Error updating procedure:", error);
            notification.error({
                message: "Error",
                description: "Terjadi kesalahan saat update procedure.",
            });
        }
    };

    const inputRefStatusProcedure = useRef(null);

    useEffect(() => {
        fetchProcedure();

        const handleKeyDown = (event) => {
            // Jika Shift + F2 ditekan, fokus ke input Autocomplete Procedure
            if (event.shiftKey && event.key === "F2") {
                inputRefStatusProcedure.current?.focus();
            }
        };

        // Menambahkan event listener untuk keydown saat komponen mount
        window.addEventListener("keydown", handleKeyDown);
        // Membersihkan event listener saat komponen unmount
        return () => {
            window.removeEventListener("keydown", handleKeyDown);
        };
    }, [trigerFetchProcedure]);

    return (
        <Card title={`Procedure`}>
            <Row gutter={16} style={{ marginBottom: 10 }}>
                <Col span={20}>
                    <Tooltip
                        title={"Shift+F2 untuk shortcut"}
                        placement="topLeft"
                    >
                        <AutoComplete
                            ref={inputRefStatusProcedure}
                            allowClear
                            onChange={() => {
                                setSelectedProcedureForm(null); // Clear the stored code
                                setSelectedProcedureDisplay(""); // Clear the display value
                            }}
                            options={anotherOptions.map((item) => {
                                const [description, code] = item;
                                return {
                                    value: `${code} - ${description}`, // Display both code and description
                                    label: (
                                        <div
                                            style={{
                                                wordBreak: "break-word",
                                                whiteSpace: "normal",
                                                overflowWrap: "break-word",
                                                display: "block",
                                            }}
                                        >
                                            <strong>{code}</strong> -{" "}
                                            <span>{description}</span>
                                        </div>
                                    ),
                                    disabled:
                                        isFinalINACBG ||
                                        selectedProcedure.includes(code),
                                };
                            })}
                            style={{ width: "100%" }}
                            onSelect={(value) => {
                                const kdPenyakit = value.split(" - ")[0]; // Extract code
                                const displayValue = value; // Full display value with name and code
                                setSelectedProcedureForm(kdPenyakit); // Store only the code
                                setSelectedProcedureDisplay(displayValue); // Display both the code and name
                            }}
                            onSearch={(text) => {
                                setSelectedProcedureDisplay(text);
                                fetchSugetProcedure(text, 1);
                            }}
                            placeholder="Cari procedure/tindakan"
                            onScroll={onScroll}
                            value={selectedProcedureDisplay}
                        />
                    </Tooltip>
                </Col>
                <Col span={4}>
                    <Button
                        type="primary"
                        size="medium"
                        style={{ width: "100%" }}
                        onClick={saveProcedure}
                        disabled={
                            loadingSaveDiag ||
                            selectedProcedureForm === null ||
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
                    dataSource={procedure}
                    size="small"
                    loading={loadingFetchProcedure}
                    rowKey="ID"
                />
            </>
            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Procedure"
                open={isModalHapusProcedureOpen}
                onOk={() => {
                    deleteProcedureId &&
                        deleteProcedure(
                            deleteProcedureId,
                            selectedProcedureForm
                        );
                }}
                onCancel={handleCancelDelProcedure}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                <p>Apakah anda yakin ingin menghapus procedure ini?</p>
            </Modal>

            <Modal
                title="Edit Procedure"
                width={800}
                open={!!procedureToEdit}
                onCancel={() => {
                    setProcedureToEdit(null);
                    setEditProcedureForm(null);
                    setEditProcedureDisplay("");
                }}
                footer={[
                    <Button
                        key="cancel"
                        onClick={() => setProcedureToEdit(null)}
                    >
                        Batal
                    </Button>,
                    <Button
                        key="submit"
                        type="primary"
                        disabled={!editProcedureForm}
                        onClick={handleUpdateProcedure}
                    >
                        Simpan
                    </Button>,
                ]}
            >
                <AutoComplete
                    allowClear
                    style={{ width: "100%" }}
                    value={editProcedureDisplay}
                    onChange={(value) => {
                        setEditProcedureDisplay(value);
                        setEditProcedureForm(null);
                    }}
                    onSelect={(value) => {
                        const code = value.split(" - ")[0];
                        setEditProcedureForm(code);
                        setEditProcedureDisplay(value);
                    }}
                    onSearch={(text) => {
                        setEditProcedureDisplay(text);
                        fetchSugetProcedure(text, 1);
                    }}
                    options={anotherOptions.map(([label, code]) => ({
                        value: `${code} - ${label}`,
                        label: (
                            <div>
                                <strong>{code}</strong> - <span>{label}</span>
                            </div>
                        ),
                        disabled:
                            isFinalINACBG || selectedProcedure.includes(code),
                    }))}
                    placeholder="Cari procedure/tindakan"
                />
            </Modal>
        </Card>
    );
}
