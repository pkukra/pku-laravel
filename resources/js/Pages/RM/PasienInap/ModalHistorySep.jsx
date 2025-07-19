import React, { useState } from "react";
import { Modal, Button, Table, Popconfirm, notification } from "antd";
import moment from "moment";
import axios from "axios";

export default function Index({ pasien, fetchNoSep }) {
    const [historySEPData, setHistorySEPData] = useState([]);
    const [loadingFecth, setloadingFecth] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingUpdateNoSep, setLoadingUpdateNoSep] = useState(false);

    const fetchHistorySep = async () => {
        setloadingFecth(true);
        axios
            .get(
                route("rm.agregate_sep", {
                    pasien_id: pasien?.PRWIKD_PASIEN,
                })
            )
            .then((response) => {
                setHistorySEPData(response?.data?.data?.histori || []);
            })
            .catch((error) => {
                console.error("Error fetching history SEP data:", error);
            })
            .finally(() => {
                setloadingFecth(false);
            });
    };

    const handleUbahSep = async (noSepBaru) => {
        setLoadingUpdateNoSep(true);
        try {
            const response = await axios.put(
                route("rm.pasien-inap.update_nomer_sep", {
                    kode_reg: pasien?.PRWINO_TRANSAKSI,
                }),
                {
                    no_rm: pasien?.PRWIKD_PASIEN,
                    new_sep: noSepBaru,
                    poli: pasien?.FMSPESIALISASIN,
                    dpjp: pasien?.FMDDOKTERN,
                }
            );
            if (response?.data?.status === "nok") {
                return notification.warning({
                    placement: "topRight",
                    description: response?.data?.message,
                });
            }

            fetchNoSep();
            return notification.success({
                placement: "topRight",
                message: "Sukses!",
                description: "Update Nomer SEP Berhasil",
            });
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoadingUpdateNoSep(false);
            setModalUpdateNoSEPOpen(false);
            setNoSepBaru(null);
        }
    };

    const columnsRujukan = [
        {
            title: "Tgl Terbit",
            dataIndex: "tglSep",
            width: 120,
            render: (text) => <>{moment(text).format("DD/MMMM/YYYY")}</>,
        },
        {
            title: "Jenis Rawat",
            dataIndex: "jnsPelayanan",
            render: (text) => <>{text === "1" ? <>INAP</> : <>JALAN</>}</>,
        },
        {
            title: "saep & Diagnosa",
            dataIndex: "diagnosa",
            render: (text, record) => (
                <>
                    {record.noSep} <br />
                    {record.diagnosa}
                </>
            ),
        },
        {
            title: "Poli Tujuan",
            dataIndex: "poliTujSep",
        },
        {
            title: "Action",
            dataIndex: "noSep",
            render: (text, record) => {
                const tanggalSep = moment(record.tglSep);
                const tanggalPeriksa = moment(pasien?.PRWITGL_MASUK);

                const isValidTanggal =
                    tanggalSep.isValid() && tanggalPeriksa.isValid();
                const selisihHari = isValidTanggal
                    ? tanggalSep.diff(tanggalPeriksa, "days")
                    : "-";

                let status = "-";
                let statusColor = "gray";

                if (isValidTanggal) {
                    if (tanggalSep.isSame(tanggalPeriksa, "day")) {
                        status = "Hari Sama";
                        statusColor = "green";
                    } else if (tanggalSep.isAfter(tanggalPeriksa, "day")) {
                        status = "Backdate..?";
                        statusColor = "red";
                    } else if (tanggalSep.isBefore(tanggalPeriksa, "day")) {
                        status = "SEP Lampau..?";
                        statusColor = "red";
                    }
                }

                return (
                    <Popconfirm
                        title="Ubah SEP?"
                        description={
                            <>
                                <div>
                                    Apakah Anda yakin ingin mengubah SEP ini?
                                </div>
                                <br />
                                <div>
                                    <strong>Tanggal Periksa:</strong>{" "}
                                    {tanggalPeriksa.isValid()
                                        ? tanggalPeriksa.format("DD/MMMM/YYYY")
                                        : "-"}
                                </div>
                                <div>
                                    <strong>Tanggal Terbit SEP:</strong>{" "}
                                    {tanggalSep.isValid()
                                        ? tanggalSep.format("DD/MMMM/YYYY")
                                        : "-"}
                                </div>
                                <div>
                                    <strong>No. SEP:</strong> {text}
                                </div>
                                <div style={{ marginTop: 8 }}>
                                    <strong>Selisih Hari:</strong> {selisihHari}{" "}
                                    hari{" "}
                                    <span style={{ color: statusColor }}>
                                        ({status})
                                    </span>
                                </div>
                            </>
                        }
                        onConfirm={() => {
                            handleUbahSep(text);
                        }}
                        okText="Ya"
                        cancelText="Batal"
                    >
                        <Button>Gunakan</Button>
                    </Popconfirm>
                );
            },
        },
    ];

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                onClick={() => {
                    setModalOpen(true);
                    fetchHistorySep();
                }}
            >
                History SEP
            </Button>

            <Modal
                title="Lihat History SEP"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
                loading={loadingUpdateNoSep}
            >
                <Table
                    dataSource={historySEPData}
                    columns={columnsRujukan}
                    size="small"
                    loading={loadingFecth}
                    rowKey="noSep"
                    pagination={false}
                />
            </Modal>
        </>
    );
}
