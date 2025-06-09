import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider } from "antd";
import axios from "axios";

import DiagnosaListINACBG from "./DiagnosaListINACBG";
import ProcedureListINACBG from "./ProcedureListINACBG";

function Index({ pasien }) {
    const [shouldRefetchData, setShouldReFetch] = useState(false);
    const [loadingFetchGroupData, setLoadingFetchGroupData] = useState(false);
    const [inacbgGroupData, setInacbgGroupData] = useState(null);

    const [isDiagnosaHasErr, setDiagnosaHasErr] = useState(true); // ambil diagnosa error dari child komponen DiagnosaListINACBG
    const [isProcedureHasErr, setProcedureHasErr] = useState(true); // ambil diagnosa error dari child komponen ProcedureListINACBG

    const [modalImportAndBridgeOpen, setModalImportAndBridgeOpen] =
        useState(false);
    const [importAndBridgeLoading, setImportAndBridgeLoading] = useState(false);

    const [modalGroupingSatuOpen, setModalGroupingSatuOpen] = useState(false);

    const handleImportAndBridgingData = async () => {
        setImportAndBridgeLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_import_idrg_to_inacbg", {
                    no_sep: pasien?.FMNOSEP,
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
                description: "sukses mengimport data dari idrg",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setShouldReFetch((prev) => !prev);
            setImportAndBridgeLoading(false);
            setModalImportAndBridgeOpen(false);
        }
    };

    const handleGroupingStageSatu = async () => {
        setImportAndBridgeLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.grouping_inacbg_stage_satu", {
                    no_sep: pasien?.FMNOSEP,
                })
            );

            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.error,
                });
            }

            if (response?.data?.response?.metadata?.code != 200) {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.response?.metadata?.message,
                });
            }

            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Sukses grouping stage satu inaCBG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setModalGroupingSatuOpen(false);
        }
    };

    const fetchINACBGData = async () => {
        setLoadingFetchGroupData(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_inacbg_group_data", {
                    no_sep: pasien?.FMNOSEP,
                })
            )
            .then((response) => {
                console.log(response);

                setInacbgGroupData(response?.data || null);
                setLoadingFetchGroupData(false);
                fetchINACBGData();
            })
            .catch((error) => {
                setLoadingFetchGroupData(false);
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingFetchGroupData(false);
            });
    };

    useEffect(() => {
        if (pasien?.FMNOSEP) {
            fetchINACBGData();
        }
    }, [pasien?.FMNOSEP]);

    const incabg_group_data = JSON.parse(
        inacbgGroupData?.response_inacbg || "{}"
    );

    const isFinalINACBG = incabg_group_data?.is_final == 1;
    const RupiahFormat = (x) => {
        const number = Number(x);
        const formatted = new Intl.NumberFormat("id-ID").format(number);
        return formatted;
    };

    return (
        <>
            <p>
                <strong>inaCBG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListINACBG
                        pasien={pasien}
                        trigerFetchDiagnosa={shouldRefetchData}
                        setDiagnosaHasErr={setDiagnosaHasErr}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListINACBG
                        pasien={pasien}
                        trigerFetchProcedure={shouldRefetchData}
                        setProcedureHasErr={setProcedureHasErr}
                    />
                </Col>
            </Row>
            <Row gutter={[5, 5]}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Divider> Hasil Grouping inaCBG </Divider>
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
                                    <td style={{ width: "15%" }}>
                                        Status Grouping
                                    </td>
                                    <td>
                                        {inacbgGroupData?.hasOwnProperty("id") ? (
                                            <strong>Sudah Grouping</strong>
                                        ) : (
                                            <strong>Belum Grouping</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "15%" }}>
                                        Status Final
                                    </td>
                                    <td>
                                        {isFinalINACBG ? (
                                            <strong>Sudah Final</strong>
                                        ) : (
                                            <strong>Belum Final</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "15%" }}>CBG Code</td>
                                    <td>{incabg_group_data?.cbg?.code}</td>
                                </tr>
                                <tr>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        CBG Description
                                    </td>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        {incabg_group_data?.cbg?.description}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tarif</td>
                                    <td>
                                        Rp{" "}
                                        {RupiahFormat(
                                            incabg_group_data?.tariff
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    )}

                    <Divider />
                    <Button
                        disabled={pasien?.FMNOSEP ? false : true}
                        type="primary"
                        onClick={() => {
                            setModalImportAndBridgeOpen(true);
                            return;
                        }}
                        style={{
                            marginRight: 5,
                            backgroundColor: " #33cc33",
                        }}
                    >
                        Import inaCBG
                    </Button>

                    <Button
                        disabled={isDiagnosaHasErr || isProcedureHasErr}
                        type="primary"
                        onClick={() => {
                            setModalGroupingSatuOpen(true);
                            return;
                        }}
                        style={{
                            marginRight: 5,
                        }}
                    >
                        Grouping
                    </Button>
                </Col>
            </Row>

            <Modal
                closable={false}
                open={modalImportAndBridgeOpen}
                title="Import dan bridging data dengan SEP tersebut:"
                onCancel={() => setModalImportAndBridgeOpen(false)}
                footer={[
                    <Button
                        disabled={importAndBridgeLoading}
                        loading={importAndBridgeLoading}
                        key="back"
                        onClick={() => setModalImportAndBridgeOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={importAndBridgeLoading}
                        disabled={importAndBridgeLoading}
                        key="submit"
                        type="primary"
                        onClick={() => handleImportAndBridgingData()}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Import & Bridging Data
                    </Button>,
                ]}
            >
                <br />
                {pasien?.FMNOSEP ? (
                    <div>
                        <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}

                <p>
                    Proses ini mengakibatkan diagnosa & prosedure yang tersimpan
                    di inaCBG terganti dengan data import dari idrg. Apakah
                    setuju untuk melanjutkan?
                </p>
            </Modal>

            <Modal
                closable={false}
                open={modalGroupingSatuOpen}
                title="Grouping InaCBG Stage Satu"
                onCancel={() => setModalGroupingSatuOpen(false)}
                footer={[
                    <Button
                        disabled={importAndBridgeLoading}
                        loading={importAndBridgeLoading}
                        key="back"
                        onClick={() => setModalGroupingSatuOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={false}
                        disabled={false}
                        key="submit"
                        type="primary"
                        onClick={() => {
                            handleGroupingStageSatu();
                            return;
                        }}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Grouping InaCBG Stage Satu
                    </Button>,
                ]}
            >
                <br />
                {pasien?.FMNOSEP ? (
                    <div>
                        <strong>Nomor SEP:</strong> {pasien?.FMNOSEP}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}
            </Modal>
        </>
    );
}

export default Index;
