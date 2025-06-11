import React, { useState, useEffect } from "react";
import { Row, Col, Button, Modal, notification, Divider, Tag } from "antd";
import axios from "axios";
import { PlusOutlined } from "@ant-design/icons";

import DiagnosaListINACBG from "./DiagnosaListINACBG";
import ProcedureListINACBG from "./ProcedureListINACBG";

function Index({
    pasien,
    inacbgGroupData,
    fetchINACBGData,
    loadingFetchInacbgData,
    isKlaimFinal,
}) {
    const [shouldRefetchData, setShouldReFetch] = useState(false);

    const [selectedCmgOption, setSelectedCmgOption] = useState([]);

    const [isDiagnosaHasErr, setDiagnosaHasErr] = useState(true); // ambil diagnosa error dari child komponen DiagnosaListINACBG
    const [isProcedureHasErr, setProcedureHasErr] = useState(true); // ambil diagnosa error dari child komponen ProcedureListINACBG

    const [modalImportAndBridgeOpen, setModalImportAndBridgeOpen] =
        useState(false);
    const [importAndBridgeLoading, setImportAndBridgeLoading] = useState(false);

    const [modalGroupingSatuOpen, setModalGroupingSatuOpen] = useState(false);
    const [modalGroupingDuaOpen, setModalGroupingDuaOpen] = useState(false);
    const [grupingLoading, setGrupingLoading] = useState(false);

    const [finalLoading, setFinalLoading] = useState(false);
    const [modalFinalOpen, setModalFinalOpen] = useState(false);

    const [reeditLoading, setReeditLoading] = useState(false);
    const [modalReEditINACBGOpen, setModalReEditINACBGOpen] = useState(false);

    const no_sep = pasien?.FMNOSEP || null;

    const handleImportAndBridgingData = async () => {
        setImportAndBridgeLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_import_idrg_to_inacbg", {
                    no_sep: no_sep,
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
            fetchINACBGData();
        }
    };

    const handleGroupingStageSatu = async () => {
        setGrupingLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.grouping_inacbg_stage_satu", {
                    no_sep: no_sep,
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
                description: "Sukses grouping stage satu INACBG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            fetchINACBGData();
            setModalGroupingSatuOpen(false);
            setGrupingLoading(false);
        }
    };

    const handleGroupingStageDua = async () => {
        setGrupingLoading(true);
        try {
            const selectedCmgOptionFormatted = selectedCmgOption
                .map((item) => item.code)
                .join("#");

            const response = await axios.post(
                route("rm.pasien-rujukan.grouping_inacbg_stage_dua", {
                    no_sep: no_sep,
                }),
                {
                    special_cmg: selectedCmgOptionFormatted,
                }
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
                description: "Sukses grouping stage dua INACBG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            fetchINACBGData();
            setModalGroupingDuaOpen(false);
            setGrupingLoading(false);
        }
    };

    const incabg_group_data = JSON.parse(
        inacbgGroupData?.response_inacbg || "{}"
    );

    const special_cmg_option = JSON.parse(
        inacbgGroupData?.special_cmg_option || "[]"
    );

    const isFinalINACBG = inacbgGroupData?.is_final == 1;
    const RupiahFormat = (x) => {
        const number = Number(x);
        const formatted = new Intl.NumberFormat("id-ID").format(number);
        return formatted;
    };

    const disableGrupSatuButton = () => {
        if (inacbgGroupData?.hasOwnProperty("id")) {
            return true; // Disable if already grouped
        }
        if (isDiagnosaHasErr || isProcedureHasErr) {
            return true; // Disable if there are errors in diagnosa or procedure
        }
        if (isFinalINACBG) {
            return true; // Disable if already finalized
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
        if (isFinalINACBG) {
            return true; // Disable if already finalized
        }
        if (incabg_group_data?.cbg?.code?.charAt(0).toUpperCase() === "X") {
            return true;
        }
        if (!inacbgGroupData?.hasOwnProperty("id")) {
            return true;
        }
        return false; // Enable otherwise
    };

    const handleFinalData = async () => {
        setFinalLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.bridging_final_inacbg", {
                    no_sep: no_sep,
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
                description: "sukses final data INACBG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setFinalLoading(false);
            setModalFinalOpen(false);
            fetchINACBGData();
        }
    };

    const handleEditUlangInacbg = async () => {
        setReeditLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.edit_ulang_inacbg", {
                    no_sep: no_sep,
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
                description: "sukses membuka kunci edit ulang data INACBG",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setReeditLoading(false);
            setModalReEditINACBGOpen(false);
            fetchINACBGData();
        }
    };

    return (
        <>
            <p>
                <strong>INACBG</strong>
            </p>
            <Row gutter={[5, 5]}>
                <Col span={12}>
                    <DiagnosaListINACBG
                        isFinalINACBG={isFinalINACBG}
                        pasien={pasien}
                        trigerFetchDiagnosa={shouldRefetchData}
                        setDiagnosaHasErr={setDiagnosaHasErr}
                        fetchINACBGData={fetchINACBGData}
                    />
                </Col>
                <Col span={12}>
                    <ProcedureListINACBG
                        isFinalINACBG={isFinalINACBG}
                        pasien={pasien}
                        trigerFetchProcedure={shouldRefetchData}
                        setProcedureHasErr={setProcedureHasErr}
                        fetchINACBGData={fetchINACBGData}
                    />
                </Col>
            </Row>
            <Row gutter={[5, 5]}>
                <Col span={12}></Col>
                <Col span={12}>
                    <Divider> Hasil Grouping INACBG </Divider>
                    {loadingFetchInacbgData ? (
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
                                        {inacbgGroupData?.hasOwnProperty(
                                            "id"
                                        ) ? (
                                            <strong>Sudah Grouping</strong>
                                        ) : (
                                            <strong>Belum Grouping</strong>
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{ width: "15%" }}>
                                        Status Final INACBG
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
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        Special CMG
                                    </td>
                                    <td
                                        style={{
                                            verticalAlign: "top",
                                        }}
                                    >
                                        {incabg_group_data?.special_cmg?.map(
                                            (item) => (
                                                <p key={item?.code}>
                                                    {item?.code} | Rp.{" "}
                                                    {RupiahFormat(item?.tariff)}{" "}
                                                    | {item?.description}
                                                </p>
                                            )
                                        )}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tarif</td>
                                    <td>
                                        Rp{" "}
                                        {RupiahFormat(
                                            incabg_group_data?.tariff || 0
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    )}
                    {special_cmg_option.length == 0 ? (
                        <></>
                    ) : (
                        <>
                            <Divider> Special CMG </Divider>
                            <table>
                                <tbody>
                                    <tr style={{ marginBottom: 10 }}>
                                        <th style={{ textAlign: "left" }}>
                                            Selected Special CMG
                                        </th>
                                        <th
                                            style={{
                                                textAlign: "left",
                                                height: 30,
                                            }}
                                        >
                                            {selectedCmgOption.length == 0 ? (
                                                <span>
                                                    Tidak ada CMG dipilih
                                                </span>
                                            ) : (
                                                selectedCmgOption.map((cmg) => (
                                                    <Tag
                                                        key={cmg.code}
                                                        closable
                                                        onClose={() =>
                                                            setSelectedCmgOption(
                                                                (prev) =>
                                                                    prev.filter(
                                                                        (
                                                                            item
                                                                        ) =>
                                                                            item.code !==
                                                                            cmg.code
                                                                    )
                                                            )
                                                        }
                                                        style={{
                                                            marginBottom: 5,
                                                        }}
                                                    >
                                                        {cmg.code}
                                                    </Tag>
                                                ))
                                            )}
                                        </th>
                                        <th></th>
                                    </tr>
                                    <tr>
                                        <th
                                            width={"35%"}
                                            style={{ textAlign: "left" }}
                                        >
                                            Code
                                        </th>
                                        <th
                                            width={"40%"}
                                            style={{ textAlign: "left" }}
                                        >
                                            Description
                                        </th>
                                        <th
                                            width={"10%"}
                                            style={{ textAlign: "center" }}
                                        >
                                            Action
                                        </th>
                                    </tr>
                                    {special_cmg_option.map((item) => (
                                        <tr key={item?.code}>
                                            <td
                                                style={{
                                                    verticalAlign: "top",
                                                }}
                                            >
                                                {item?.code}
                                            </td>
                                            <td
                                                style={{
                                                    verticalAlign: "top",
                                                }}
                                            >
                                                {item?.description} <br />
                                            </td>
                                            <td
                                                style={{
                                                    verticalAlign: "top",
                                                    textAlign: "center",
                                                }}
                                            >
                                                <a
                                                    onClick={() => {
                                                        const exists =
                                                            selectedCmgOption.find(
                                                                (selected) =>
                                                                    selected.code ===
                                                                    item.code
                                                            );
                                                        if (!exists) {
                                                            setSelectedCmgOption(
                                                                (prev) => [
                                                                    ...prev,
                                                                    item,
                                                                ]
                                                            );
                                                        }
                                                    }}
                                                >
                                                    <PlusOutlined />
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </>
                    )}

                    <Button
                        disabled={
                            (no_sep ? false : true) || isFinalINACBG
                        }
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
                        Import INACBG
                    </Button>

                    <Button
                        disabled={disableGrupSatuButton()}
                        type="primary"
                        onClick={() => {
                            setModalGroupingSatuOpen(true);
                            return;
                        }}
                        style={{
                            marginRight: 5,
                        }}
                    >
                        Grouping Stage-1
                    </Button>

                    <Button
                        disabled={
                            // tambhan karena stage 2 untuk top up, tentunya jika topup kosong maka disable
                            special_cmg_option.length == 0 ||
                            selectedCmgOption.length == 0
                        }
                        type="primary"
                        onClick={() => {
                            setModalGroupingDuaOpen(true);
                            return;
                        }}
                        style={{
                            marginRight: 5,
                        }}
                    >
                        Grouping Stage-2
                    </Button>

                    {!isFinalINACBG ? (
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
                            disabled={isKlaimFinal}
                            type="primary"
                            style={{ backgroundColor: " #F3732F" }}
                            variant="solid"
                            onClick={() => {
                                setModalReEditINACBGOpen(true);
                                return;
                            }}
                        >
                            Edit Ulang INACBG
                        </Button>
                    )}
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
                {no_sep ? (
                    <div>
                        <strong>Nomor SEP:</strong> {no_sep}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}

                <p>
                    Proses ini mengakibatkan diagnosa & prosedure yang tersimpan
                    di INACBG terganti dengan data import dari idrg. Apakah
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
                        disabled={grupingLoading}
                        loading={grupingLoading}
                        key="back"
                        onClick={() => setModalGroupingSatuOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        disabled={grupingLoading}
                        loading={grupingLoading}
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
                {no_sep ? (
                    <div>
                        <strong>Nomor SEP:</strong> {no_sep}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}
            </Modal>

            <Modal
                closable={false}
                open={modalGroupingDuaOpen}
                title="Grouping InaCBG Stage Dua"
                onCancel={() => setModalGroupingDuaOpen(false)}
                footer={[
                    <Button
                        disabled={grupingLoading}
                        loading={grupingLoading}
                        key="back"
                        onClick={() => setModalGroupingDuaOpen(false)}
                    >
                        Cancel
                    </Button>,
                    <Button
                        loading={grupingLoading}
                        disabled={grupingLoading}
                        key="submit"
                        type="primary"
                        onClick={() => {
                            handleGroupingStageDua();
                            return;
                        }}
                        style={{ backgroundColor: " #33cc33" }}
                    >
                        Ok, Grouping InaCBG Stage Dua
                    </Button>,
                ]}
            >
                <br />
                {no_sep ? (
                    <div>
                        <strong>Nomor SEP:</strong> {no_sep}
                    </div>
                ) : (
                    <strong>Belum ada data SEP</strong>
                )}
                <br />
                Selected Special CMG
                <br />
                {selectedCmgOption.length === 0 ? (
                    <span>Tidak ada CMG dipilih</span>
                ) : (
                    selectedCmgOption.map((cmg) => (
                        <Tag
                            key={cmg.code}
                            closable
                            onClose={() =>
                                setSelectedCmgOption((prev) =>
                                    prev.filter(
                                        (item) => item.code !== cmg.code
                                    )
                                )
                            }
                            style={{ marginBottom: 5 }}
                        >
                            {cmg.code}
                        </Tag>
                    ))
                )}
            </Modal>

            <Modal
                open={modalFinalOpen}
                title="Final INACBG"
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
                        disabled={no_sep !== null ? false : true}
                        key="submit"
                        type="primary"
                        loading={finalLoading}
                        onClick={() => handleFinalData()}
                        style={{ backgroundColor: " #cc66ff" }}
                    >
                        Ok, Final INACBG
                    </Button>,
                ]}
            >
                {no_sep ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {no_sep}
                        </p>
                    </div>
                ) : (
                    <p>
                        <strong>Belum ada data SEP</strong>
                    </p>
                )}
            </Modal>

            <Modal
                open={modalReEditINACBGOpen}
                title="Edit Ulang INACBG"
                onCancel={() => setModalReEditINACBGOpen(false)}
                footer={[
                    <Button
                        key="back"
                        onClick={() => setModalReEditINACBGOpen(false)}
                        loading={reeditLoading}
                    >
                        Cancel
                    </Button>,
                    <Button
                        style={{ backgroundColor: " #F3732F" }}
                        loading={reeditLoading}
                        variant="solid"
                        onClick={() => {
                            handleEditUlangInacbg();
                            return;
                        }}
                    >
                        Ok, Edit Ulang INACBG
                    </Button>,
                ]}
            >
                {no_sep ? (
                    <div>
                        <p>
                            <strong>Nomor SEP:</strong> {no_sep}
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
