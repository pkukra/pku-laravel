import React, { useState, useEffect, useRef } from "react";
import { usePage } from "@inertiajs/react";
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
import moment from "moment";

export default function Index({ pasien, isFinalIDRG, fetchIDRGData }) {
    const { props } = usePage();
    const role = props?.auth?.user?.role?.name;

    const columns = [
        {
            title: "Kode",
            dataIndex: "code",
            key: "code",
            width: 30,
            render: (_, record) => {
                return (
                    <>
                        {record.code} <br />
                        {record.is_primary == "1" ? (
                            <small>Primary</small>
                        ) : (
                            <small>Secondary</small>
                        )}
                    </>
                );
            },
        },
        {
            title: "Tindakan",
            dataIndex: "description",
            key: "description",
            render: (text, record) => {
                return (
                    <>
                        {text}
                        {(role === "klaim" || role === "superadmin") && (
                            <>
                                <br />
                                <small style={{ fontSize: 10 }}>
                                    created_at:{" "}
                                    {moment(record.created_at).format(
                                        "D MMMM YYYY HH:mm:ss"
                                    )}{" "}
                                    created_by: {record.created_by}
                                </small>
                            </>
                        )}
                    </>
                );
            },
        },
        {
            title: "Multiplicity",
            dataIndex: "multiplicity",
            key: "multiplicity",
            width: 20,
            align: "center",
            render: (item, record) => {
                return (
                    <>
                        <a
                            disabled={isFinalIDRG}
                            onClick={() => {
                                setMultiplicityUpdate(record?.multiplicity);
                                setIsModalSetMultiplicityOpen(true);
                                setMultiplicityProcedureData(record);
                                return;
                            }}
                        >
                            {item}
                        </a>
                    </>
                );
            },
        },
        {
            title: "Action",
            key: "action",
            align: "center",
            width: 100,
            render: (_, record) => (
                <>
                    <Button
                        disabled={
                            record?.is_primary == "1" || loading || isFinalIDRG
                        }
                        size="small"
                        block
                        variant="outlined"
                        onClick={() => {
                            setPrimaryProcedureId(record.id);
                            setIsModalSetPrimaryOpen(true);
                        }}
                    >
                        Set Primary
                    </Button>{" "}
                    <br />
                    <Button
                        style={{ marginTop: 5 }}
                        disabled={
                            isFinalIDRG ||
                            (loadingDeleteProcedure &&
                                record.id === deleteProcedureId)
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

    const [multiplicityProcedureData, setMultiplicityProcedureData] =
        useState(null);
    const [multiplicityUpdate, setMultiplicityUpdate] = useState(1);
    const [isModalSetMultiplicityOpen, setIsModalSetMultiplicityOpen] =
        useState(false);

    const [primaryProcedureId, setPrimaryProcedureId] = useState(null);
    const [isModalSetPrimaryOpen, setIsModalSetPrimaryOpen] = useState(false);
    const [loadingPrimaryProcedure, setLoadingPrimaryProcedure] =
        useState(false);

    const [procedure, setProcedure] = useState([]); // State untuk menyimpan data procedure
    const [loadingFetchProcedure, setLoadingFetchProcedure] = useState(true); // Loading state

    const [loadingProcedureAlert, setLoadingProcedureAlert] = useState(false); // State untuk menyimpan data diagnosa
    const [procedureAlert, setProcedureAlert] = useState(null); // State untuk menyimpan data procedure

    const no_sep = pasien?.FMNOSEP || null;
    let pasien_id = pasien?.FRPPASIEN_ID;
    let kode_reg = pasien?.FRPNOTRANSAKSIKJ;
    let customer_id = pasien?.FRPCUSTOMER_ID;
    if (pasien?.JENIS_RAWAT == "ranap") {
        customer_id = pasien?.PRWIKD_CUSTOMER;
        kode_reg = pasien?.FTNO_TRANSAKSI;
        pasien_id = pasien?.FTKD_PASIEN;
    }

    const disableInvalidSEP = () => {
        if (
            ["X002", "X003"].includes(customer_id) &&
            pasien?.IS_SEP_VALID == false &&
            pasien?.LANJUT_RANAP == false
        ) {
            return true;
        }
        return false;
    };

    // Fungsi untuk mengambil data procedure
    const fetchProcedure = () => {
        setLoadingFetchProcedure(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_procedure_idrg", {
                    kode_reg: kode_reg,
                    no_sep: no_sep,
                })
            )
            .then((response) => {
                setSelectedProcedure(
                    response.data.data.map((item) => item.code)
                );
                setProcedure(response?.data?.data || []); // Simpan data yang diterima ke dalam state
                const procedureCodesArr = response?.data?.data?.map(
                    (item) => item.code
                );
                fetchAlertProcedure(procedureCodesArr);
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
            })
            .finally(() => {
                setLoadingFetchProcedure(false);
            });
    };

    const fetchAlertProcedure = (procedureCodes = []) => {
        if (procedureCodes.length === 0) return;
        setLoadingProcedureAlert(true);
        axios
            .post(route("rm.icd.list_alert_by_codes"), {
                codes: procedureCodes, // kirim array langsung ["I63.9","I10",...]
            })
            .then((response) => {
                setProcedureAlert(response?.data?.data || []);
            })
            .catch((error) => {
                console.error("Error fetching alert data:", error);
            })
            .finally(() => {
                setLoadingProcedureAlert(false);
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
        if (
            ["X002", "X003"].includes(customer_id) &&
            !no_sep &&
            pasien?.LANJUT_RANAP == false
        ) {
            return notification.error({
                placement: "top",
                message: "Tidak dapat menyimpan procedure",
                description: "Pasien BPJS tapi belum ada SEP.",
            });
        }
        setLoadingSaveDiag(true);
        const payload = {
            code: selectedProcedureForm,
            pasien_id: pasien_id,
            multiplicity: multiplicityForm,
            ...(no_sep ? { no_sep: no_sep } : { no_transaksikj: kode_reg }),
        };
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_procedure_idrg"),
                payload
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
            fetchIDRGData(); // Fetch the latest IDRG data after saving
            fetchProcedure();
            setLoadingSaveDiag(false);
            setSelectedProcedureForm(null);
            setSelectedProcedureDisplay(null);
            setMultiplicityForm(1);

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
                route("rm.pasien-rujukan.delete_procedure_idrg", {
                    id: id,
                })
            )
            .then((response) => {
                // Menghapus kode procedure dari selectedProcedure setelah berhasil dihapus
                setSelectedProcedure((prevSelectedProcedure) =>
                    prevSelectedProcedure.filter((item) => item !== kode)
                );
                fetchIDRGData(); // Fetch the latest IDRG data after saving
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

    const makePrimaryProcedure = (id) => {
        setLoadingDeleteProcedure(true); // Set loading true saat mulai menghapus
        axios
            .post(
                route("rm.pasien-rujukan.procedure_idrg_set_primary", {
                    id: id,
                })
            )
            .then(() => {
                fetchIDRGData(); // Fetch the latest IDRG data after saving
                fetchProcedure(); // Memanggil ulang untuk mendapatkan data procedure terbaru
            })
            .catch((error) => {
                console.error("Error fetching procedure data:", error);
            })
            .finally(() => {
                setIsModalSetPrimaryOpen(false);
                setLoadingPrimaryProcedure(false);
            });
    };

    const updateMultiplicity = async () => {
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.procedure_idrg_udpate_multiplicity"),
                {
                    id: multiplicityProcedureData?.id,
                    multiplicity: multiplicityUpdate,
                }
            );
            fetchIDRGData(); // Fetch the latest IDRG data after saving
            fetchProcedure();
        } catch (error) {
            console.error("Error updating multiplicity:", error);
        } finally {
            setMultiplicityProcedureData(null);
            setMultiplicityUpdate(null);
            setIsModalSetMultiplicityOpen(false);
        }
        return;
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
    }, [pasien]);

    return (
        <>
            <Card title={`Procedure`}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={17}>
                        <Tooltip
                            title={"Shift+F2 untuk shortcut"}
                            placement="topLeft"
                        >
                            <AutoComplete
                                disabled={isFinalIDRG || disableInvalidSEP()}
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
                                                wordBreak: "break-word",
                                                whiteSpace: "normal",
                                                overflowWrap: "break-word",
                                                display: "block",
                                                color:
                                                    item.validcode != 1
                                                        ? "red"
                                                        : "inherit", // Tambahkan warna merah jika invalid
                                            }}
                                        >
                                            <strong>
                                                {item.code}{" "}
                                                {item.asterisk == 1 && <>* </>}
                                            </strong>{" "}
                                            {item.validcode != 1 && (
                                                <span>(Invalid) </span>
                                            )}
                                            <span>{item.description}</span>
                                        </div>
                                    ),
                                    disabled:
                                        // selectedProcedure.includes(item.code) ||
                                        item.validcode != 1, // Disable jika sudah dipilih atau invalid
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
                            disabled={isFinalIDRG}
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
                                loadingSaveDiag ||
                                selectedProcedureForm === null
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

                {/* Modal for set primary */}
                <Modal
                    title="Set Primary Procedure"
                    open={isModalSetPrimaryOpen}
                    onOk={() => {
                        primaryProcedureId &&
                            makePrimaryProcedure(primaryProcedureId);
                    }}
                    onCancel={() => {
                        setIsModalSetPrimaryOpen(false);
                    }}
                    okText="Ya"
                    cancelText="Tidak"
                    okButtonProps={{ primary: true }}
                    loading={loadingPrimaryProcedure}
                >
                    <p>
                        Apakah anda yakin ingin menjadikan procedure ini
                        primary?
                    </p>
                </Modal>

                {/* Modal update multiplicity */}
                <Modal
                    title="Update Multiplicity"
                    open={isModalSetMultiplicityOpen}
                    onOk={() => {
                        updateMultiplicity();
                    }}
                    onCancel={() => {
                        setIsModalSetMultiplicityOpen(false);
                    }}
                    okText="Simpan"
                    cancelText="Cancel"
                    okButtonProps={{ primary: true }}
                    loading={loadingPrimaryProcedure}
                >
                    <Row gutter={16} style={{ marginBottom: 10 }}>
                        <Col span={5}>Kode</Col>
                        <Col>: {multiplicityProcedureData?.code}</Col>
                    </Row>

                    <Row gutter={16} style={{ marginBottom: 10 }}>
                        <Col span={5}>Description</Col>
                        <Col>: {multiplicityProcedureData?.description}</Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={5}>Multiplicity</Col>
                        <Col>
                            :{" "}
                            <InputNumber
                                min={1}
                                value={multiplicityUpdate}
                                onChange={setMultiplicityUpdate}
                            />
                        </Col>
                    </Row>
                </Modal>
            </Card>
            <Card
                title={`Syarat/Kelengkapan Data Pengkodean Procedure`}
                style={{ marginTop: 10 }}
                loading={loadingProcedureAlert}
            >
                {procedureAlert?.length < 1 && <>Belum ada data</>}

                {procedureAlert?.map((item, index) => (
                    <p key={index} style={{ marginBottom: 10 }}>
                        {item.icd_code} - {item.description}
                    </p>
                ))}
            </Card>
        </>
    );
}
