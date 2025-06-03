import React, { useState, useEffect } from "react";
import { Card, Button, Modal } from "antd";
import moment from "moment";

const cleanText = (html) =>
    html
        .replace(/<\/?p>/g, "") // Hapus tag <p> dan </p>
        .replace(/&nbsp;/g, " ") // Ganti &nbsp; dengan spasi
        .replace(/\s+/g, " ") // Hapus enter & spasi berlebih
        .trim(); // Hapus spasi di awal dan akhir

export default function Index({ pasien, dataTransaksi }) {
    const [resumeData, setResumeData] = useState(null);
    const [loadingResume, setLoadingResume] = useState(false);
    const [sugestDariAi, setSugestDariAi] = useState([]);
    const [modalAiOpen, setModalAiOpen] = useState(false);
    const [loadingFetchingSugestKodeAI, setLoadingFetchingSugestKodeAI] =
        useState(false);

    const fetchResume = async () => {
        setLoadingResume(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_resume", {
                    kode_reg: dataTransaksi.FRPNOTRANSAKSI,
                })
            )
            .then((response) => {
                setResumeData(response?.data?.data || null);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingResume(false);
            });
    };

    const handleGetAiSugest = async () => {
        setModalAiOpen(true);
        setLoadingFetchingSugestKodeAI(true);
        const anamnesa = cleanText(resumeData?.FS_ANAMNESA || "");
        const diagnosa = cleanText(resumeData?.FS_DIAGNOSA || "");

        try {
            const response = await axios.post(
                "http://10.10.10.225:3000/get-icd10",
                {
                    anamnesa: anamnesa,
                    diagnosa: diagnosa,
                }
            );
            setSugestDariAi(response?.data?.ICD10_Codes || []);
            console.log(response?.data?.ICD10_Codes);
        } catch (error) {
            console.error("Error fetching ai sugest:", error);
        } finally {
            setLoadingFetchingSugestKodeAI(false);
        }
    };

    useEffect(() => {
        fetchResume();
    }, []);

    return (
        <>
            <Card loading={loadingResume} style={{ marginBottom: "5px" }}>
                <table
                    className="tw-table-zebra tw-table-xs"
                    style={{ width: "100%", textAlign: "left" }}
                >
                    <tbody>
                        <tr>
                            <th
                                style={{
                                    width: "25%",
                                }}
                            >
                                Tanggal Periksa
                            </th>
                            <td>
                                {moment(dataTransaksi.FRPTGL).format(
                                    "DD/MM/YYYY"
                                )}{" "}
                                {moment(dataTransaksi.FRPJAM).format("HH:mm")}
                            </td>
                        </tr>
                        <tr>
                            <th>Dokter</th>
                            <td>{dataTransaksi.FMDDOKTERN}</td>
                        </tr>
                        <tr>
                            <th>Poli</th>
                            <td>{dataTransaksi.FMPKLINIKN}</td>
                        </tr>
                        <tr>
                            <th>Status Dokter</th>
                            <td>{(dataTransaksi.RUBBER=="1")?"Rawat Bersama":"DPJP Utama"}</td>
                        </tr>
                        <tr>
                            <th>ID Transaksi</th>
                            <td>{dataTransaksi.FRPNOTRANSAKSI} / {dataTransaksi.FRPNOTRANSAKSIKJ}</td>
                        </tr>
                    </tbody>
                </table>
            </Card>
            <Card title="Resume Pasien" loading={loadingResume}>
                <table
                    className="tw-table tw-table-xs"
                    style={{ width: "100%" }}
                >
                    <tbody align="left">
                        <tr>
                            <th style={{ width: "25%", verticalAlign: "top" }}>
                                Anamnesa (S)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_ANAMNESA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Pemeriksaan Fisik (O)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_CATATAN_FISIK,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Diagnosa (A)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_DIAGNOSA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}>
                                Tindakan/Planning (P)
                            </th>
                            <td
                                style={{ verticalAlign: "top" }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_TINDAKAN,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th style={{ verticalAlign: "top" }}></th>
                            <td>
                                <Button
                                    color="purple"
                                    variant="solid"
                                    onClick={handleGetAiSugest}
                                >
                                    AI Sugest
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <Modal
                title="Saran Kode Diagnosa dari AI"
                open={modalAiOpen}
                onOk={() => {}}
                onCancel={() => {
                    setModalAiOpen(false);
                }}
                okText={false}
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                {loadingFetchingSugestKodeAI ? (
                    <>Berfikirr..Berfikirr..Berfikirr...</>
                ) : (
                    <>
                        {JSON.stringify(sugestDariAi)}
                        <table>
                            <tbody>
                                {sugestDariAi.map((item, index) => (
                                    <tr key={index}>
                                        <td>{item.Code}</td>
                                        <td>{item.Diagnosis}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </>
                )}
            </Modal>
        </>
    );
}
