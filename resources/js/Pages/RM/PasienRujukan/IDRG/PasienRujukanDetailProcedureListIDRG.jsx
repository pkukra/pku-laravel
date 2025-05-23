import React, { useState, useEffect, useRef } from "react";
import {
    Modal,
    Spin,
    Card,
    AutoComplete,
    Row,
    Col,
    notification,
    Table,
    Button,
    Tooltip,
    InputNumber,
} from "antd";
import { PlusOutlined, LoadingOutlined } from "@ant-design/icons";
import axios from "axios";

export default function Index({ pasien }) {
    const columns = [
        {
            title: "Status",
            dataIndex: "is_primary",
            key: "is_primary",
            render: (_, record) => {
                if (record?.is_primary == "1") {
                    return <>Primary</>;
                }
                return <>Secondary</>;
            },
            width: 100,
        },
        {
            title: "Kode",
            dataIndex: "code",
            key: "code",
            width: 50,
        },
        {
            title: "Penyakit",
            dataIndex: "description",
            key: "description",
        },
        {
            title: "Multiplicity",
            dataIndex: "multiplicity",
            key: "multiplicity",
            width: 20,
        },
        {
            title: "Action",
            key: "action",
            align: "center",
            render: (_, record) => (
                <>
                    <Button
                        disabled={
                            record?.is_primary == "1" || loading
                        }
                        size="small"
                        block
                        variant="outlined"
                        onClick={() => showSetPrimaryConfirm(record)}
                    >
                        Set Primary
                    </Button>{" "}
                    <br />
                    <Button
                        style={{ marginTop: 5 }}
                        disabled={
                            loadingDeleteProcedure &&
                            record.id === deleteProcedureId
                        }
                        block
                        size="small"
                        variant="outlined"
                        color="danger"
                        onClick={() => showDeleteConfirm(record)}
                    >
                        hapus
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
    const [multiplicityForm, setMultiplicityForm] = useState(1);

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

    // Fungsi untuk mengambil data procedure
    const fetchProcedure = () => {
        setLoadingFetchProcedure(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_procedure_idrg", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setSelectedProcedure(
                    response.data.data.map((item) => item.code)
                );
                setProcedure(response?.data?.data || []); // Simpan data yang diterima ke dalam state
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
                route("rm.pasien-rujukan.cari_procedure_im"),
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
            fetchSugetProcedure(value, page + 1);
        }
    };

    // Function to save procedure
    const saveProcedure = async () => {
        setLoadingSaveDiag(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_procedure_idrg"),
                {
                    code: selectedProcedureForm,
                    no_transaksikj: pasien.FRPNOTRANSAKSIKJ,
                    pasien_id: pasien.FRPPASIEN_ID,
                    multiplicity: multiplicityForm,
                }
            );

            if (response?.data?.status == "ok") {
                return notification.success({
                    placement: "topRight",
                    message: "Sukses!",
                    description: "Procedure berhasil ditambahkan.",
                });
            }
            return notification.error({
                placement: "topRight",
                message: "Terjadi Kesalahan!",
                description: "Procedure gagal ditambahkan.",
            });
        } catch (error) {
            console.error("Error saving procedure:", error);
        } finally {
            fetchProcedure();
            setLoadingSaveDiag(false);
            setSelectedProcedureForm(null);
            setSelectedProcedureDisplay(null);

            inputRefStatusProcedure.current?.focus();
        }
        return;
    };

    // Function to show the modal with the procedure info for deletion
    const showDeleteConfirm = (item) => {
        setDeleteProcedureId(item.id); // Set the current procedure to be deleted
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
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
            })
            .finally(() => {
                setLoadingDeleteProcedure(false);
                setIsModalHapusProcedureOpen(false);
            });
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
    }, []);

    return (
        <Card title={`Procedure`}>
            <Row gutter={16} style={{ marginBottom: 10 }}>
                <Col span={17}>
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
                            options={anotherOptions.map((item) => ({
                                value: `${item.code} - ${item.description}`, // Display both code and name
                                label: (
                                    <div
                                        style={{
                                            wordBreak: "break-word", // Ensure text wraps
                                            whiteSpace: "normal", // Allow wrapping long words
                                            overflowWrap: "break-word", // Break long words if necessary
                                            display: "block", // Ensure block level behavior for wrapping
                                        }}
                                    >
                                        <strong>{item.code}</strong> -{" "}
                                        <span>{item.description}</span>
                                    </div>
                                ),
                                disabled: selectedProcedure.includes(item.code), // Disable if already selected
                            }))}
                            style={{ width: "100%" }}
                            onSelect={(value) => {
                                const kdPenyakit = value.split(" - ")[0]; // Extract code
                                const displayValue = value; // Full display value with name and code
                                setSelectedProcedureForm(kdPenyakit); // Store only the code
                                setSelectedProcedureDisplay(displayValue); // Display both the code and name
                            }}
                            onSearch={(text) => {
                                setSelectedProcedureDisplay(text); // Update the display value during search
                                fetchSugetProcedure(text, 1); // Trigger the fetch for suggestions
                            }}
                            placeholder="Cari procedure/tindakan"
                            onScroll={onScroll} // Attach scroll event for lazy loading
                            value={selectedProcedureDisplay} // Show both code and name in the input
                        />
                    </Tooltip>
                </Col>
                <Col span={3}>
                    <InputNumber
                        placeholder="Multiplicity"
                        value={multiplicityForm}
                        style={{ width: "100%" }}
                        onChange={setMultiplicityForm}
                        min={1}
                    />
                </Col>
                <Col span={4}>
                    <Button
                        type="primary"
                        size="medium"
                        style={{ width: "100%" }}
                        onClick={saveProcedure}
                        disabled={
                            loadingSaveDiag || selectedProcedureForm === null
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
                    rowKey="id"
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
        </Card>
    );
}
