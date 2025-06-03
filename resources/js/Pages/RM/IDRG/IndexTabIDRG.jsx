import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListIDRG from "./DiagnosaListIDRG";
import ProcedureListIDRG from "./ProcedureListIDRG";

function Index({ pasien, golbalSEP, setDisableINACBG }) {
    const [modalBridgeOpen, setModalBridgeOpen] = useState(false);
    const [bridgingLoading, setBridgingLoading] = useState(false);
    const [diagnosaTab, setDiagnosaTab] = useState([]);
    const [idrgGroupData, setIdrgGroupData] = useState(null);
    const [loadingFetchGroupData, setLoadingFetchGroupData] = useState(false);

    const [modalFinalOpen, setModalFinalOpen] = useState(false);
    const [finalLoading, setFinalLoading] = useState(false);

    const [modalReEditIDRGOpen, setModalReEditIDRGOpen] = useState(false);
    const [reeditLoading, setReeditLoading] = useState(false);

    const handleBridgingData = async () => {
        setBridgingLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_data_idrg", {
                    no_sep: golbalSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: response?.data?.response?.metadata?.message,
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setBridgingLoading(false);
            setModalBridgeOpen(false);
            fetchIDRGData();
        }
    };

    const handleFinalData = async () => {
        setFinalLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_final_idrg", {
                    no_sep: golbalSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: response?.data?.response?.metadata?.message,
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setFinalLoading(false);
            setModalFinalOpen(false);
            fetchIDRGData();
        }
    };

    const handleEditUlangData = async () => {
        setReeditLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.edit_ulang_idrg", {
                    no_sep: golbalSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code === 400) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: response?.data?.response?.metadata?.message,
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setReeditLoading(false);
            setModalReEditIDRGOpen(false);
            fetchIDRGData();
        }
    };

    const fetchIDRGData = async () => {
        setLoadingFetchGroupData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_idrg_group_data", {
                    kode_reg_kj: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setIdrgGroupData(response?.data?.data || null);
                setLoadingFetchGroupData(false);
            })
            .catch((error) => {
                setLoadingFetchGroupData(false);
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchGroupData(false);
            });
    };

    const isFinalIDRG = idrgGroupData?.is_final == 1;

    const disableBridgeButton = () => {
        if (isFinalIDRG) {
            return true; // Disable if already finalized
        }
        if (diagnosaTab.length == 0) {
            return true; // Disable if no diagnoses
        }
        if (
            pasien.FRPCUSTOMER_ID != "X002" &&
            pasien.FRPCUSTOMER_ID != "X003"
        ) {
            return true; // Disable jika bukan X002 atau X003
        }
        return false; // Enable otherwise
    };

    const disableFinalButton = () => {
        if (isFinalIDRG) {
            return true; // Disable if already finalized
        }
        if (eklaim_group_data?.mdc_number === "36") {
            return true;
        }
        if (idrgGroupData === null) {
            return true;
        }
        return false; // Enable otherwise
    };

    useEffect(() => {
        fetchIDRGData();
    }, []);

    useEffect(() => {
        const isFinal = idrgGroupData?.is_final == "1";
        setDisableINACBG(!isFinal); // kebalikan: jika final, maka disable = false
    }, [idrgGroupData]);

    const eklaim_group_data = JSON.parse(
        idrgGroupData?.response_eklaim || "{}"
    );

    return (
        <>
            <p>
                <strong>iDRG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListIDRG
                        isFinalIDRG={isFinalIDRG}
                        pasien={pasien}
                        setDiagnosaTab={setDiagnosaTab}
                        fetchIDRGData={fetchIDRGData}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListIDRG
                        pasien={pasien}
                        isFinalIDRG={isFinalIDRG}
                        fetchIDRGData={fetchIDRGData}
                    />
                </Col>
            </Row>
            <Row gutter={[5, 5]}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Divider> Hasil Grouping iDRG </Divider>
                    {loadingFetchGroupData ? (
                        <p>Loading...</p>
                    ) : (
                        <table
                            style={{
                                borderCollapse: "collapse",
                                width: "100%",
                                margin: 10,
                            }}
                        >
                            <tbody>
                                <tr>
                                    <td style={{ width: "20%" }}>
                                        Status Grouping
                                    </td>
                                    <td>
                                        {idrgGroupData ? (
                                            <strong>Sudah Grouping</strong>
                                        ) : (
                                            <strong>Belum Grouping</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Status Final
                                    </td>
                                    <td>
                                        {isFinalIDRG ? (
                                            <strong>Sudah Final</strong>
                                        ) : (
                                            <strong>Belum Final</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td>MDC Number</td>
                                    <td>{eklaim_group_data?.mdc_number}</td>
                                </tr>
                                <tr>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        MDC Description
                                    </td>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        {eklaim_group_data?.mdc_description}
                                    </td>
                                </tr>
                                <tr>
                                    <td>DRG Code</td>
                                    <td>{eklaim_group_data?.drg_code}</td>
                                </tr>
                                <tr>
                                    <td>DRG Description</td>
                                    <td>
                                        {eklaim_group_data?.drg_description}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    )}

                    <Divider />
                    <Button
                        disabled={disableBridgeButton()}
                        type="primary"
                        onClick={() => {
                            setModalBridgeOpen(true);
                            return;
                        }}
                        style={{ marginRight: 5, backgroundColor: " #33cc33" }}
                    >
                        Bridge iDRG
                    </Button>
                    {!isFinalIDRG ? (
                        <Button
                            type="primary"
                            onClick={() => {
                                setModalFinalOpen(true);
                                return;
                            }}
                            disabled={disableFinalButton()}
                            style={{ backgroundColor: " #cc66ff" }}
                        >
                            Final Data
                        </Button>
                    ) : (
                        <Button
                            color="danger"
                            variant="solid"
                            onClick={() => {
                                setModalReEditIDRGOpen(true);
                                return;
                            }}
                        >
                            Edit Ulang iDRG
                        </Button>
                    )}
                </Col>
            </Row>
            <Modal
                open={modalBridgeOpen}
                title="Bridging Data IDRG"
                onCancel={() => setModalBridgeOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalBridgeOpen(false)}
                        loading={bridgingLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        disabled={golbalSEP !== null ? false : true}
                        key="submit"
                        type="primary"
                        loading={bridgingLoading}
                        onClick={() => handleBridgingData()}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Bridging Data
                    </Button>,
                ]}
            >
                {golbalSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {golbalSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>
            <Modal
                open={modalFinalOpen}
                title="Final Data IDRG"
                onCancel={() => setModalFinalOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalFinalOpen(false)}
                        loading={finalLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        disabled={golbalSEP !== null ? false : true}
                        key="submit"
                        type="primary"
                        loading={finalLoading}
                        onClick={() => handleFinalData()}
                        style={{ backgroundColor: " #cc66ff" }}
                    >
                        Ok, Final Data
                    </Button>,
                ]}
            >
                {golbalSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {golbalSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>
            <Modal
                open={modalReEditIDRGOpen}
                title="Edit Ulang iDRG"
                onCancel={() => setModalReEditIDRGOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalReEditIDRGOpen(false)}
                        loading={reeditLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={reeditLoading}
                        color="danger"
                        variant="solid"
                        onClick={() => {
                            handleEditUlangData();
                            return;
                        }}
                    >
                        Ok, Edit Ulang iDRG
                    </Button>,
                ]}
            >
                {golbalSEP ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {golbalSEP}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>
        </>
    );
}

export default Index;
