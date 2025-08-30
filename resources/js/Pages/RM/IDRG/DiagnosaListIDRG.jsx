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
    Tooltip,
    Button,
} from "antd";
import { PlusOutlined, LoadingOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";

export default function Index({
    pasien,
    setDiagnosaTab,
    isFinalIDRG,
    fetchIDRGData,
}) {
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
            title: "Penyakit",
            dataIndex: "description",
            key: "description",
            render: (text, record) => {
                return (
                    <>
                        {text} <br />
                        {record.is_code_warning == 1 && (
                            <>
                                <strong style={{ color: "red" }}>
                                    (Rawan Pending){" "}
                                </strong>
                            </>
                        )}
                        {(role === "klaim" || role === "superadmin") && (
                            <>
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
            title: "Action",
            key: "action",
            align: "center",
            width: 100,
            render: (_, record) => (
                <>
                    <Button
                        disabled={
                            record?.is_primary == "1" ||
                            loadingPrimaryDiagnosa ||
                            isFinalIDRG
                        }
                        size="small"
                        block
                        variant="outlined"
                        onClick={() => {
                            if (record.accpdx == "N") {
                                return notification.error({
                                    placement: "top",
                                    description:
                                        "Diagnosa ini tidak bisa dijadikan primary",
                                });
                            }
                            showSetPrimaryConfirm(record);
                            return;
                        }}
                    >
                        Set Primary
                    </Button>{" "}
                    <br />
                    <Button
                        style={{ marginTop: 5 }}
                        disabled={
                            isFinalIDRG ||
                            (loadingDeleteDiagnosa &&
                                record.id == deleteDiagnosaId)
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

    const [anotherOptions, setAnotherOptions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [selectedDiagnosaForm, setSelectedDiagnosaForm] = useState(null);
    const [loadingSaveDiag, setLoadingSaveDiag] = useState(false); // Loading state for each diagnosa
    const [selectedDiagnosaDisplay, setSelectedDiagnosaDisplay] = useState(""); // Stores the full value for display

    const [
        selectedDiagnosaAcceptedPrimaryForm,
        setSelectedDiagnosaAcceptedPrimaryForm,
    ] = useState(null);

    const [deleteDiagnosaId, setDeleteDiagnosaId] = useState(null); // Track which diagnosa is being deleted
    const [isModalHapusDiagnosaOpen, setIsModalHapusDiagnosaOpen] =
        useState(false);
    const [loadingDeleteDiagnosa, setLoadingDeleteDiagnosa] = useState(false); // State loading untuk penghapusan diagnosa

    const [primaryDiagnosaId, setPrimaryDiagnosaId] = useState(null); // Track which diagnosa is being set as primary
    const [isModalSetPrimaryOpen, setIsModalSetPrimaryOpen] = useState(false);
    const [loadingPrimaryDiagnosa, setLoadingPrimaryDiagnosa] = useState(false);

    const [selectedDiagnosa, setSelectedDiagnosa] = useState([]); // untuk disable diagnosa terpiluh, agar saat menampilkan list diagnosa tidak terpilih 2 kali
    const [diagnosa, setDiagnosa] = useState([]); // State untuk menyimpan data diagnosa
    const [loadingFetchDiagnosa, setLoadingFetchDiagnosa] = useState(false); // Loading state

    const [loadingDiagnosaAlert, setLoadingDiagnosaAlert] = useState(false); // State untuk menyimpan data diagnosa
    const [diagnosaAlert, setDiagnosaAlert] = useState(null); // State untuk menyimpan data diagnosa

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
        if (pasien?.LANJUT_RANAP == true) {
            return false;
        }
        if (
            ["X002", "X003"].includes(customer_id) &&
            pasien?.IS_SEP_VALID == false
        ) {
            return true;
        }
        return false;
    };

    // Fungsi untuk mengambil data diagnosa
    const fetchDiagnosa = () => {
        setLoadingFetchDiagnosa(true);
        axios
            .get(
                route("rm.pasien-rujukan.list_diagnosa_idrg", {
                    kode_reg: kode_reg,
                    no_sep: no_sep,
                })
            )
            .then((response) => {
                setSelectedDiagnosa(
                    response.data.data.map((item) => item.code)
                );
                setDiagnosa(response?.data?.data || []); // Simpan data yang diterima ke dalam state
                setDiagnosaTab(response?.data?.data || []); // Simpan data yang diterima ke dalam state parent component
                const diagnosaCodesArr = response?.data?.data?.map(
                    (item) => item.code
                );
                fetchAlertDiagnosa(diagnosaCodesArr);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchDiagnosa(false);
            });
    };

    const fetchAlertDiagnosa = (diagnosaCodes = []) => {
        if (diagnosaCodes.length === 0) return;

        setLoadingDiagnosaAlert(true);

        axios
            .post(route("rm.icd.list_alert_by_codes"), {
                codes: diagnosaCodes, // kirim array langsung ["I63.9","I10",...]
            })
            .then((response) => {
                setDiagnosaAlert(response?.data?.data || []);
            })
            .catch((error) => {
                console.error("Error fetching alert data:", error);
            })
            .finally(() => {
                setLoadingDiagnosaAlert(false);
            });
    };

    // Fetch diagnosa with lazy loading support
    const fetchSugetDiagnosa = async (query = "a", pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.cari_penyakit_im"),
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
        if (
            diagnosa?.length < 1 &&
            selectedDiagnosaAcceptedPrimaryForm == "N"
        ) {
            return notification.error({
                placement: "top",
                description: "Diagnosa ini tidak bisa dijadikan primary",
            });
        }

        if (
            ["X002", "X003"].includes(customer_id) &&
            !no_sep &&
            pasien?.LANJUT_RANAP == false
        ) {
            return notification.error({
                placement: "top",
                message: "Tidak dapat menyimpan diagnosa",
                description: "Pasien BPJS tapi belum ada SEP.",
            });
        }
        setLoadingSaveDiag(true);
        const payload = {
            code: selectedDiagnosaForm,
            pasien_id: pasien_id,
            ...(no_sep ? { no_sep: no_sep } : { no_transaksikj: kode_reg }),
        };

        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_diagnosa_idrg"),
                payload
            );

            if (response?.data?.status === "ok") {
                return notification.success({
                    placement: "topRight",
                    message: "Sukses!",
                    description: "Diagnosa berhasil ditambahkan.",
                });
            }
            return notification.error({
                placement: "topRight",
                message: "Terjadi Kesalahan!",
                description: "Diagnosa gagal ditambahkan.",
            });
        } catch (error) {
            console.error("Error saving diagnosa:", error);
        } finally {
            fetchIDRGData();
            fetchDiagnosa();
            setLoadingSaveDiag(false);
            setSelectedDiagnosaForm(null);
            setSelectedDiagnosaDisplay(null);

            inputRefStatusDdiagnosa.current?.focus();
        }
        return;
    };

    // Function to show the modal with the diagnosa info for deletion
    const showDeleteConfirm = (item) => {
        setDeleteDiagnosaId(item.id); // Set the current diagnosa to be deleted
        setIsModalHapusDiagnosaOpen(true); // Show the modal
    };

    // Function to show the modal with the diagnosa info for deletion
    const showSetPrimaryConfirm = (item) => {
        setPrimaryDiagnosaId(item.id); // Set the current diagnosa to be deleted
        setIsModalSetPrimaryOpen(true); // Show the modal
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
                route("rm.pasien-rujukan.delete_diagnosa_idrg", {
                    id: id,
                })
            )
            .then((response) => {
                // Menghapus kode diagnosa dari selectedDiagnosa setelah berhasil dihapus
                setSelectedDiagnosa((prevSelectedDiagnosa) =>
                    prevSelectedDiagnosa.filter((item) => item !== kode)
                );
                fetchDiagnosa(); // Memanggil ulang untuk mendapatkan data diagnosa terbaru
                fetchIDRGData();
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingDeleteDiagnosa(false);
                setIsModalHapusDiagnosaOpen(false);
            });
    };

    const makePrimaryDiagnoda = (id) => {
        setLoadingDeleteDiagnosa(true); // Set loading true saat mulai menghapus
        axios
            .post(
                route("rm.pasien-rujukan.diagnosa_idrg_set_primary", {
                    id: id,
                })
            )
            .then((response) => {
                fetchDiagnosa(); // Memanggil ulang untuk mendapatkan data diagnosa terbaru
                fetchIDRGData();
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setIsModalSetPrimaryOpen(false);
                setLoadingPrimaryDiagnosa(false);
            });
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
    }, [pasien]);

    return (
        <>
            <Card title={`Diagnosa`}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={20}>
                        <Tooltip
                            title={"Shift+F1 untuk shortcut"}
                            placement="topLeft"
                        >
                            <AutoComplete
                                disabled={isFinalIDRG || disableInvalidSEP()}
                                ref={inputRefStatusDdiagnosa}
                                loading={loading}
                                allowClear
                                onChange={() => {
                                    setSelectedDiagnosaForm(null); // Clear the stored code
                                    setSelectedDiagnosaDisplay(""); // Clear the display value
                                }}
                                options={anotherOptions.map((item) => ({
                                    value: `${item.code} - ${item.description} - ${item.accpdx}`,
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
                                                        : "inherit", // Warna merah jika invalid
                                            }}
                                        >
                                            <strong>
                                                {item.code}{" "}
                                                {item.asterisk == 1 && <>* </>}
                                            </strong>{" "}
                                            -{" "}
                                            {item.validcode != 1 && (
                                                <span>(Invalid) </span>
                                            )}
                                            <span>{item.description}</span>
                                        </div>
                                    ),
                                    disabled:
                                        // selectedDiagnosa.includes(item.code) ||
                                        item.validcode != 1,
                                }))}
                                style={{ width: "100%" }}
                                onSelect={(value) => {
                                    const kdPenyakit = value.split(" - ")[0]; // Extract code
                                    const accpdx = value.split(" - ")[2]; // Extract code
                                    const displayValue = value; // Full display value with name and code
                                    setSelectedDiagnosaForm(kdPenyakit); // Store only the code
                                    setSelectedDiagnosaDisplay(displayValue); // Display both the code and name
                                    setSelectedDiagnosaAcceptedPrimaryForm(
                                        accpdx
                                    );
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
                        </Tooltip>
                    </Col>
                    <Col span={4}>
                        <Button
                            type="primary"
                            size="medium"
                            style={{ width: "100%" }}
                            onClick={saveDiagnosa}
                            disabled={
                                loadingSaveDiag || selectedDiagnosaForm === null
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
                        rowKey="id"
                        rowClassName={(record) => {
                            return record.is_code_warning == 1
                                ? "rawan-pending"
                                : "";
                        }}
                    />
                </>
                {/* Modal for Confirming Deletion */}
                <Modal
                    title="Hapus Diagnosa"
                    open={isModalHapusDiagnosaOpen}
                    onOk={() => {
                        deleteDiagnosaId &&
                            deleteDiagnosa(
                                deleteDiagnosaId,
                                selectedDiagnosaForm
                            );
                    }}
                    onCancel={handleCancelDelDiagnosa}
                    okText="Ya"
                    cancelText="Tidak"
                    okButtonProps={{ danger: true }}
                >
                    <p>Apakah anda yakin ingin menghapus diagnosa ini?</p>
                </Modal>

                {/* Modal for set primary */}
                <Modal
                    title="Set Primary Diagnosa"
                    open={isModalSetPrimaryOpen}
                    onOk={() => {
                        primaryDiagnosaId &&
                            makePrimaryDiagnoda(primaryDiagnosaId);
                    }}
                    onCancel={() => {
                        setIsModalSetPrimaryOpen(false);
                    }}
                    okText="Ya"
                    cancelText="Tidak"
                    okButtonProps={{ primary: true }}
                >
                    <p>
                        Apakah anda yakin ingin menjadikan diagnosa ini primary?
                    </p>
                </Modal>
            </Card>

            <Card
                title={`Syarat/Kelengkapan Data Pengkodean Diagnosa`}
                style={{ marginTop: 10 }}
                loading={loadingDiagnosaAlert}
            >
                {diagnosaAlert?.length < 1 && <>Belum ada data</>}
                {diagnosaAlert?.map((item, index) => (
                    <p key={index} style={{ marginBottom: 10 }}>
                        {item.icd_code} - {item.description}
                    </p>
                ))}
            </Card>
        </>
    );
}
